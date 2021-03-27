<?php

namespace App\Http\Controllers\Front\Payments;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Shop\Carts\Repositories\Interfaces\CartRepositoryInterface;
use App\Shop\Checkout\CheckoutRepository;
use App\Shop\Orders\Repositories\OrderRepository;
use App\Shop\OrderStatuses\OrderStatus;
use App\Shop\OrderStatuses\Repositories\OrderStatusRepository;
use App\Shop\Customers;
use App\Shop\Shipping\ShippingInterface;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;
use Shippo_Shipment;
use Shippo_Transaction;
use App\Notifications\OrderProcessed;

class WhatsappController extends Controller
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepo;

    /**
     * @var int $shipping
     */
    private $shippingFee;

    private $rateObjectId;

    private $shipmentObjId;

    private $billingAddress;

    private $carrier;

    /**
     * BankTransferController constructor.
     *
     * @param Request $request
     * @param CartRepositoryInterface $cartRepository
     * @param ShippingInterface $shippingRepo
     */
    public function __construct(
        Request $request,
        CartRepositoryInterface $cartRepository,
        ShippingInterface $shippingRepo
    )
    {
        $this->cartRepo = $cartRepository;
        $fee = 0;
        $rateObjId = null;
        $shipmentObjId = null;
        $billingAddress = $request->input('billing_address');

        if ($request->has('rate')) {
            if ($request->input('rate') != '') {

                $rate_id = $request->input('rate');
                $rates = $shippingRepo->getRates($request->input('shipment_obj_id'));
                $rate = collect($rates->results)->filter(function ($rate) use ($rate_id) {
                    return $rate->object_id == $rate_id;
                })->first();

                $fee = $rate->amount;
                $rateObjId = $rate->object_id;
                $shipmentObjId = $request->input('shipment_obj_id');
                $this->carrier = $rate;
            }
        }

        $this->shippingFee = $fee;
        $this->rateObjectId = $rateObjId;
        $this->shipmentObjId = $shipmentObjId;
        $this->billingAddress = $billingAddress;
    }

    public function index(){
        return view('front.send-to-whatsapp-redirect', [
            'subtotal' => $this->cartRepo->getSubTotal(),
            'shipping' => $this->shippingFee,
            'tax' => $this->cartRepo->getTax(),
            'total' => $this->cartRepo->getTotal(2, $this->shippingFee),
            'rateObjectId' => $this->rateObjectId,
            'shipmentObjId' => $this->shipmentObjId,
            'billingAddress' => $this->billingAddress
        ]);
    }

    public function store(Request $request){
        $checkoutRepo = new CheckoutRepository;
        $orderStatusRepo = new OrderStatusRepository(new OrderStatus);
        $os = $orderStatusRepo->findByName('ordered');

        $order = $checkoutRepo->buildCheckoutItems([
            'reference' => Uuid::uuid4()->toString(),
            'courier_id' => 1, // @deprecated
            'customer_id' => $request->user()->id,
            'address_id' => $request->input('billing_address'),
            'order_status_id' => $os->id,
            'payment' => strtolower(config('bank-transfer.name')),
            'discounts' => 0,
            'total_products' => $this->cartRepo->getSubTotal(),
            'total' => $this->cartRepo->getTotal(2, $this->shippingFee),
            'total_shipping' => $this->shippingFee,
            'total_paid' => 0,
            'tax' => $this->cartRepo->getTax()
        ]);

        $request->user()->notify(new OrderProcessed($order));

        Cart::destroy();
        return redirect()->route('accounts', ['tab' => 'orders'])->with('message', 'Order successful!');
    }
}

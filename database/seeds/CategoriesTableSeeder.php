<?php

use App\Shop\Categories\Category;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        factory(Category::class)->create();
        // \DB::table('categories')->insert(array (
        //     0 => array(
        //         'name' => 'Cake',
        //         'slug' => 'cake',
        //         'description' => 'Esse cumque velit fuga. Nisi voluptate magni pariatur voluptatibus aut illo. Qui sunt voluptatem voluptates fugiat nobis est enim temporibus.',
        //         'cover' => 'categories/9E9GT3Osp6Bf8zZDPkf0QB9TE5NJeeffUAQu8MQB.png',
        //         'status' => '1'
        //     ),
        // ));
    }
}
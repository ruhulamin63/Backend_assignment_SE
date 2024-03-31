<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Console\Command;

class StoreTableData extends Command
{
    protected $signature = 'store:data {c_name} {ca_image} {p_name} {p_description} {p_price} {p_image}';
    protected $description = 'Store data into the categories and products tables';

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Extracting command arguments
        $categoryName = $this->argument('c_name');
        $categoryImage = $this->argument('ca_image');
        $categoryParentId = $this->argument('ca_parent_id');

        $productName = $this->argument('p_name');
        $productDescription = $this->argument('p_description');
        $productPrice = $this->argument('p_price');
        $productImage = $this->argument('p_image');


        // Create category if not exists
        $category = Category::firstOrCreate([
            'name' => $categoryName,
            'image' => $categoryImage,
            'parent_id' => $categoryParentId
        ]);

        // Create product under the category
        $category->products()->create([
            'name' => $productName,
            'description' => $productDescription,
            'price' => $productPrice,
            'image' => $productImage
        ]);

        $this->info('Data stored successfully!');
    }
}

<?php
    require './vendor/autoload.php';

    use ProductImporter\Csv;
    
    $object = (new Csv('./csv/products.csv'))
        ->groupBy('PLU') // Required
        ->addRule('SHOE_EU', 'size', range(20, 50)) // Optional
        ->addRule('SHOE_UK', 'size', array_merge(array_map(function($value) { return $value . ' (Child)'; }, range(1, 20, .5)), range(1, 20, .5))) // Optional
        ->addRule('CLOTHING_SHORT', 'size', ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', 'XXXXL']) // Optional
        ->sortByRule('sizeSort') // Optional
        ->mapToStructure([
            'PLU', 
            'name',
            'SKU',
            'sizes' => [
                'SKU',
                'size'
            ]
        ]) // Optional: You can remove this and just have the grouped products
        ->get(); // required to output

print('<pre>');
print_r($object);
echo('</pre>');
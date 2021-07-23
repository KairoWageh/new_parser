<?php

namespace App\Feeds\Vendors\AFF;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $dims = [];
    private ?array $attributes = null;
    private string $product = '';
    private string $upc = '';
    private array $files = [];
    private int $avail = 0;
    private string $full_description = '';
    private array $short_description = [];
    private string $video_src = '';


    public const SITE_LINK = 'https://www.affinitechstore.com';

    public function beforeParse(): void
    {
        $body = $this->getHtml( 'article.productView-description--full div#tab-description' );
        $arr = explode( '<br>', $body );
        foreach ( $arr as $val ) {
            $val = strip_tags( $val );
            if ( str_contains( $val, '_' ) ) {
                $this->mpn = $val;
            }

            // get dimensions from dimensions text, in full description
            elseif(stripos($val, 'dimensions') !== false){
                $dimensions_txt = substr($val, stripos($val, 'dimensions'));
                if ( str_contains( $dimensions_txt, 'x' ) ) {
                    $ar = explode( 'x', $dimensions_txt );
                    $this->dims[ 'x' ] = StringHelper::getFloat( $ar[ 0 ] );
                    if(str_contains($ar[ 1 ], 'x')){
                        $array = explode( 'x', $ar[ 1 ] );
                        $this->dims[ 'y' ] = StringHelper::getFloat( $array[ 0 ] );
                        if(str_contains($array[ 1 ], ' ')) {
                            $z_array = explode('x', $ar[1]);
                            $this->dims['z'] = StringHelper::getFloat($z_array[0]);
                        }
                        }else{
                        $this->dims[ 'y' ] = StringHelper::getFloat( $ar[ 1 ] );
                    }
                }
            }

            elseif ( stripos( $val, 'MSRP' ) !== false ) {
                $this->list_price = StringHelper::getMoney( $val );
            }
        }
        if($this->exists('div#tab-warranty')){
            $warranty_section = $this->getContent('div#tab-warranty div.productView-description-tabContent');
            $warranty_section_string = implode('</li><li>', $warranty_section);
            $this->short_description = explode('</li><li>', $warranty_section_string);
        }


        if($this->exists('div.tabs-contents div#tab-addition div.productView-info-value a')){
            $this->filter( 'div.tabs-contents div#tab-addition div.productView-info-value a' )->each( function ( ParserCrawler $c ) {
                if(stripos($c->attr('href'), '.pdf') !== false){
                    $short_link = $c->attr('href');
                    $file_link = self::SITE_LINK.$short_link;
                    $this->files = [
                        [
                            'link' => $file_link,
                            'name' => $this->getProduct()
                        ]
                    ];
                }
            } );
        }

        if($this->exists('#tab-description .productView-description-tabContent')){

            $this->full_description = trim( $this->getHtml( '[itemprop="description"]' ) );
            $features = "Features";
            $mounting = "Mounting";
            $specification = "Specification";
            if(str_contains($this->full_description, $features)){
                $this->full_description = substr($this->full_description, 0, strpos($this->full_description, $features));
            }
            if(str_contains($this->full_description, $mounting)){
                $mounting_txt = substr($this->full_description, stripos($this->full_description, $mounting));
                $this->short_description[] = $mounting_txt;
                $this->full_description = substr($this->full_description, 0, strpos($this->full_description, $mounting));
            }
            if(str_contains($this->full_description, $specification)){
                $specification_txt = substr($this->full_description, stripos($this->full_description, $specification));
                $specification_array = explode('</li><li>', $specification_txt);
                foreach ($specification_array as $item){
                    preg_match('/^[^\-]*-\D*/', $item, $number);
                    if(isset($number[0])){
                        $attr_value = strlen($number[0]);
                        $attr_key = chop($item, $attr_value);
                        $this->attributes[$attr_key] = $attr_value;
                    }
                }
                $this->full_description = substr($this->full_description, 0, strpos($this->full_description, $specification));
            }
        }
        $key = $this->getContent('dl.productView-info dt.productView-info-name');
        $val = $this->getContent('dl.productView-info dd.productView-info-value');
        $combined = array_combine($key, $val);

        foreach ($combined as $key => $val){
            if($key === 'UPC:'){
                $this->upc = $val;
            }
            if($key === 'Condition:'){
                $this->attributes[ StringHelper::normalizeSpaceInString( $key ) ] = StringHelper::normalizeSpaceInString( $val );
            }
        }
    }

    public function getMpn(): string
    {
        return $this->getText('.productView-info-value--sku');
    }

    public function getProduct(): string
    {
        return $this->product ?: $this->getText( 'h1.productView-title' );
    }

    public function getDescription(): string
    {
        return $this->full_description;
    }

    public function getShortDescription(): array
    {

        return $this->short_description;
    }

    public function getCostToUs(): float
    {
        if($this->exists('.price--main')){
            return $this->getMoney( '.price--main' );
        }else{
            return 0.0;
        }
    }

    public function getListPrice(): ?float
    {
        return $this->getMoney( '.price--rrp' );
    }

    public function getUpc(): ?string
    {
        return $this->upc;
    }

    public function getDimX(): ?float
    {
        return $this->dims[ 'x' ] ?? null;
    }

    public function getDimY(): ?float
    {
        return $this->dims[ 'y' ] ?? null;
    }

    public function getDimZ(): ?float
    {
        return $this->dims[ 'z' ] ?? null;
    }
    

    public function getAttributes(): ?array
    {
        return $this->attributes ?? null;
    }

    public function getImages(): array
    {
        $indexs = [];
        $images = [];
        if($this->exists('li.productView-imageCarousel-main-item')){
            $index = $this->getAttr('li.productView-imageCarousel-main-item', 'data-slick-index');
            array_push($indexs, $index);
        }
        for($image=0; $image<count($indexs); $image++){
            if($this->exists('li.productView-imageCarousel-main-item a')){
                if($indexs[$image] == $this->getAttr('li.productView-imageCarousel-main-item', 'data-slick-index')){
                    $image_link = $this->getAttr( 'li.productView-imageCarousel-main-item a', 'href' );
                    echo PHP_EOL.'image::'.$image_link.PHP_EOL;
                    array_push($images, $image_link);
                }
            }
        }
        return $images;
    }

    public function getProductFiles(): array
    {
        return $this->files;
    }

    public function getAvail(): ?int
    {
        if($this->exists('form.form--addToCart input#form-action-addToCart')){
            $this->avail = self::DEFAULT_AVAIL_NUMBER;
        }
        return $this->avail;
    }

    public function getVideos(): array
    {
        if($this->exists('div#tab-description div.productView-description-tabContent')){
            $this->video_src = $this->getAttr('div#tab-description div.productView-description-tabContent p iframe', 'src');
            return [[
                'name' => '',
                'provider' => 'youtube',
                'video' => $this->video_src
            ]];
        }else{
            return [];
        }
    }
}

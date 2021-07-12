<?php

namespace App\Feeds\Vendors\AFF;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private array $dims = [];
    private ?array $attrs = null;
    private string $product = '';
    private string $upc = '';
    private $keys = [];
    private $vals = [];
    private $files = [];
    private int $avail = 0;

    public function beforeParse(): void
    {
        $body = $this->getHtml( 'article.productView-description--full div#tab-description' );
        $arr = explode( '<br>', $body );
        foreach ( $arr as $val ) {
            $val = strip_tags( $val );
            if ( str_contains( $val, '_' ) ) {
                $this->mpn = $val;
            }elseif(stripos($val, 'dimensions') !== false){
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

        if($this->exists('div.tabs-contents div#tab-addition div.productView-info-value a')){
            $this->filter( 'div.tabs-contents div#tab-addition div.productView-info-value a' )->each( function ( ParserCrawler $c ) {
                if(stripos($c->attr('href'), '.pdf') !== false){
                    $this->files = [
                        'link' => $c->attr('href'),
                        'name' => $this->getProduct()
                    ];
                }
            } );
        }

        $key = $this->getContent('dl.productView-info dt.productView-info-name');
        array_push($this->keys, $key);
        $val = $this->getContent('dl.productView-info dd.productView-info-value');
        array_push($this->vals, $val);
        $combined = array_merge($this->keys, $this->vals);
        foreach ($combined as $key => $val){
            $count = count($val);
            for($i=0; $i<$count; $i++){
                $attr_key = $combined[0][$i];
                $attr_val = $combined[1][$i];
                if($attr_key === 'UPC:'){
                    $this->upc = $attr_val;
                }
                if($attr_key === 'Condition:'){
                    $this->attrs[ StringHelper::normalizeSpaceInString( $attr_key ) ] = StringHelper::normalizeSpaceInString( $attr_val );
                }
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
        $full_desc = trim( $this->getHtml( '[itemprop="description"]' ) );
        $pattern = "Features";
        if(str_contains($full_desc, $pattern)){
            $full_desc = substr($full_desc, 0, strpos($full_desc, $pattern));
        }
        return $full_desc;
    }

    public function getShortDescription(): array
    {
        $short_description = [];
        if($this->exists('div#tab-description')){
            $short_description += $this->getContent('div#tab-warranty div.productView-description-tabContent');
        }
        if($this->exists('div#tab-warranty')){
            $short_description += $this->getContent('div#tab-warranty div.productView-description-tabContent');
        }
        return $short_description;
    }

    public function getCostToUs(): float
    {
        return $this->getMoney( '.price--main' );
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
        return $this->attrs ?? null;
    }

    public function getImages(): array
    {
        if($this->exists('li.productView-imageCarousel-main-item a')){
            return [ $this->getAttr( 'li.productView-imageCarousel-main-item a', 'href' ) ];
        }else{
            return [];
        }
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

}
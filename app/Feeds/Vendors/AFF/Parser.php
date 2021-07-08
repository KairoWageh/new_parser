<?php

namespace App\Feeds\Vendors\AFF;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{

    private ?array $attrs = null;
    private string $product = '';
    private string $upc = '';
    private $keys = [];
    private $vals = [];

    public function beforeParse(): void
    {
        $body = $this->getHtml( 'div.productView-description-tabContent' );

        $arr = explode( '<br>', $body );
        foreach ( $arr as $val ) {
            $val = strip_tags( $val );
            if ( str_contains( $val, '_' ) ) {
                $this->mpn = $val;
            }
            elseif ( str_contains( $val, ' x ' ) ) {
                $ar = explode( 'x', $val );
                $this->dims[ 'x' ] = StringHelper::getFloat( $ar[ 0 ] );
                $this->dims[ 'y' ] = StringHelper::getFloat( $ar[ 1 ] );

            }
            elseif ( stripos( $val, 'MSRP' ) !== false ) {
                $this->list_price = StringHelper::getMoney( $val );
            }
        }
        $key = $this->getContent('dl.productView-info dt.productView-info-name');
        array_push($this->keys, $key);
        $val = $this->getContent('dl.productView-info dd.productView-info-value');
        array_push($this->vals, $val);
        $combined = array_merge($this->keys, $this->vals);
        foreach ($combined as $key=>$val){
            $count = count($val);
            for($i=0; $i<$count; $i++){
                $attr_key = $combined[0][$i];
                $attr_val = $combined[1][$i];
                if($attr_key === 'UPC:'){
                    $this->upc = $attr_val;
                }
                $this->attrs[ StringHelper::normalizeSpaceInString( $attr_key ) ] = StringHelper::normalizeSpaceInString( $attr_val );
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
         return trim( $this->getHtml( '[itemprop="description"]' ) );

    }

    public function getShortDescription(): array
    {
        if ( $this->exists( 'div.productView-description-tabContent ul' ) ) {
            return $this->getContent( 'div.productView-description-tabContent ul li' );
        }
        return [];
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

    public function getAttributes(): ?array
    {
        return $this->attrs ?? null;
    }

    public function getImages(): array
    {
        if($this->exists('.productView-imageCarousel-main-item img')){
            return $this->getSrcImages( '.productView-imageCarousel-main-item img' );
        }else{
            return [];
        }
    }

    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }

}
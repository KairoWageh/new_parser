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
    private string $mpn = '';
    private int $list_price = 0;
    private array $attribute_key = [];
    private array $attribute_value = [];
    private string $full_description = '';

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
                        [
                            'link' => $c->attr('href'),
                            'name' => $this->getProduct()
                        ]
                    ];
                }
            } );
        }

        if($this->exists('#tab-description .productView-description-tabContent')){

            $this->full_description = trim( $this->getHtml( '[itemprop="description"]' ) );
            $pattern = "Features";
            $regexpression = '/<table> .*/';
            $replacemnet = '';

            if(preg_match($regexpression, $this->full_description) == 1){
                $html = str_get_html($regexpression);
                $rows = $html->find('tr');
                foreach ($rows as $row){
                    foreach ($row->children() as $cell){
                        if($this->exists('td.td')){
                            $this->attribute_key[] = $this->getText('td.td');
                        }
                        if($this->exists('td.td2')){
                            $this->attribute_value[] = $this->getText('td.td2');
                        }
                        $this->attributes = array_combine($this->attribute_key, $this->attribute_value);
                    }
                }
            }
            $this->full_description = preg_replace($regexpression, $replacemnet, $this->full_description);
            if(str_contains($this->full_description, $pattern)){
                $this->full_description = substr($this->full_description, 0, strpos($this->full_description, $pattern));
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
        return $this->attributes ?? null;
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
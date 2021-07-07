<?php

namespace App\Feeds\Vendors\AFF;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private const MAIN_DOMAIN = 'https://www.affinitechstore.com/store';

//    private array $dims = [];
    private array $shorts = [];
    private ?array $attrs = null;
//    private ?float $shipping_weight = null;
    private ?float $list_price = null;
    private ?int $avail = null;
    private string $mpn = '';
    private string $product = '';
    private string $upc = '';

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
            elseif ( stripos( $val, 'Not for sale' ) !== false ) {
                $this->shorts[] = StringHelper::normalizeSpaceInString( $val );
            }
        }
        $this->filter( 'dl.productView-info' )->each( function ( ParserCrawler $c ) {
            if ( str_contains( $c->text(), ':' ) ) {
//                [ $key, $val ] = explode( ':', $c->text() );
                $key = $this->getText('dt.productView-info-name');
                $val = $this->getText('dd.productView-info-value');

                if ( $key === 'UPC:'  ) {
//                    $this->upc = StringHelper::normalizeSpaceInString( $val );
                    $this->upc = $val;
                }
                else {
                    $this->attrs[ StringHelper::normalizeSpaceInString( $key ) ] = StringHelper::normalizeSpaceInString( $val );
                }
            }
            elseif ( stripos( $c->text(), 'New' ) !== false ) {
                $this->avail = StringHelper::getFloat( $c->text() );
            }
//            elseif ( stripos( $c->text(), 'out of stock' ) !== false ) {
//                $this->avail = 0;
//            }
            else {
                $this->shorts[] = StringHelper::normalizeSpaceInString( $c->text() );
            }
        } );
    }

    public function getMpn(): string
    {
        return $this->getText('.productView-info-value--sku');
    }

    public function getProduct(): string
    {
        return $this->product ?: $this->getText( 'h1.productView-title' );
    }

//    public function getDescription(): string
//    {
//        return $this->getText('div.productView-description-tabContent');
//    }

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
//        return $this->list_price;
    }



    public function getUpc(): ?string
    {
        return $this->upc;
    }

    public function getAttributes(): ?array
    {
        return $this->attrs ?? null;
    }

//    public function getImages(): array
//    {
//        return [ self::MAIN_DOMAIN . $this->getAttr( '.productView-imageCarousel-main-item img', 'src' ) ];
//    }

    public function getImages(): array
    {
//        return $this->exists('.productView-imageCarousel-main-item img')? $this->getSrcImages( '.productView-imageCarousel-main-item img' ): false;
        if($this->exists('.productView-imageCarousel-main-item img')){
            return $this->getSrcImages( '.productView-imageCarousel-main-item img' );
        }else{
            return [];
        }
    }

    public function getAvail(): ?int
    {
//        return self::DEFAULT_AVAIL_NUMBER;
        return $this->avail;
    }
}
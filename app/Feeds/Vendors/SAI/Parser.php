<?php

namespace App\Feeds\Vendors\SAI;

use App\Feeds\Parser\HtmlParser;
use App\Feeds\Utils\ParserCrawler;
use App\Helpers\StringHelper;

class Parser extends HtmlParser
{
    private const MAIN_DOMAIN = 'https://www.affinitechstore.com/store';

//    private array $dims = [];
//    private array $shorts = [];
//    private ?array $attrs = null;
//    private ?float $shipping_weight = null;
//    private ?float $list_price = null;
    private ?int $avail = null;
    private string $mpn = '';
    private string $product = '';

//    public function beforeParse(): void
//    {
//        $body = $this->getHtml( 'div#tab-description div.productView-description-tabContent' );
//
//        $arr = explode( '<br>', $body );
//        foreach ( $arr as $val ) {
//            $val = strip_tags( $val );
//            if ( str_contains( $val, '_' ) ) {
//                $this->mpn = $val;
//            }
////            elseif ( str_contains( $val, ' x ' ) ) {
////                $ar = explode( 'x', $val );
////                $this->dims[ 'x' ] = StringHelper::getFloat( $ar[ 0 ] );
////                $this->dims[ 'y' ] = StringHelper::getFloat( $ar[ 1 ] );
////
////            }
//            elseif ( stripos( $val, 'Price' ) !== false ) {
//                $this->list_price = StringHelper::getMoney( $val );
//            }
////            elseif ( stripos( $val, 'Not for sale' ) !== false ) {
////                $this->shorts[] = StringHelper::normalizeSpaceInString( $val );
////            }
//        }
////        $this->filter( 'ul#productDetailsList li' )->each( function ( ParserCrawler $c ) {
////            if ( str_contains( $c->text(), ':' ) ) {
////                [ $key, $val ] = explode( ':', $c->text() );
////                if ( stripos( $key, 'Shipping Weight' ) !== false ) {
////                    $this->shipping_weight = StringHelper::getFloat( $val );
////                }
////                elseif ( stripos( $key, 'Model' ) !== false ) {
////                    $this->product = StringHelper::normalizeSpaceInString( $val );
////                }
////                else {
////                    $this->attrs[ StringHelper::normalizeSpaceInString( $key ) ] = StringHelper::normalizeSpaceInString( $val );
////                }
////            }
////            elseif ( stripos( $c->text(), 'in stock' ) !== false ) {
////                $this->avail = StringHelper::getFloat( $c->text() );
////            }
////            elseif ( stripos( $c->text(), 'out of stock' ) !== false ) {
////                $this->avail = 0;
////            }
////            else {
////                $this->shorts[] = StringHelper::normalizeSpaceInString( $c->text() );
////            }
////        } );
//    }

    public function getMpn(): string
    {
        return $this->mpn;
    }

    public function getProduct(): string
    {
        return $this->product ?: $this->getText( 'h1.productView-title' );
    }

    public function getCostToUs(): float
    {
        return StringHelper::getMoney( $this->getMoney( '.price' ) );
    }

    public function getImages(): array
    {
        return [ self::MAIN_DOMAIN . $this->getAttr( '.productView-imageCarousel-main-item img', 'src' ) ];
    }
    public function getAvail(): ?int
    {
        return self::DEFAULT_AVAIL_NUMBER;
    }
}
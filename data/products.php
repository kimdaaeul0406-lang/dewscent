<?php
// data/products.php
// DewScent 상품 리스트 (지금은 PHP 배열, 나중에 DB로 바꿔도 됨)

$products = [
    [
        'id' => 1,
        'name' => 'Morning Dew',
        'type' => '향수',
        'price' => 89000,
        'originalPrice' => 110000,
        'rating' => 4.8,
        'reviews' => 128,
        'badge' => 'BEST',
        'desc' => '아침 이슬처럼 맑고 청량한 향기입니다.',
    ],
    [
        'id' => 2,
        'name' => 'Rose Garden',
        'type' => '바디미스트',
        'price' => 65000,
        'originalPrice' => null,
        'rating' => 4.9,
        'reviews' => 89,
        'badge' => 'NEW',
        'desc' => '로맨틱한 장미 정원을 거니는 듯한 우아한 향기입니다.',
    ],
    [
        'id' => 3,
        'name' => 'Golden Hour',
        'type' => '향수',
        'price' => 105000,
        'originalPrice' => null,
        'rating' => 4.7,
        'reviews' => 156,
        'badge' => null,
        'desc' => '황금빛 노을처럼 따스하고 포근한 향기입니다.',
    ],
    [
        'id' => 4,
        'name' => 'Forest Mist',
        'type' => '디퓨저',
        'price' => 78000,
        'originalPrice' => 98000,
        'rating' => 4.6,
        'reviews' => 72,
        'badge' => 'SALE',
        'desc' => '숲속의 신선한 공기를 담은 청량한 향기입니다.',
    ],
    [
        'id' => 5,
        'name' => 'Ocean Breeze',
        'type' => '섬유유연제',
        'price' => 32000,
        'originalPrice' => null,
        'rating' => 4.5,
        'reviews' => 203,
        'badge' => null,
        'desc' => '바다 바람처럼 시원하고 깨끗한 향기입니다.',
    ],
    [
        'id' => 6,
        'name' => 'Velvet Night',
        'type' => '향수',
        'price' => 125000,
        'originalPrice' => null,
        'rating' => 4.9,
        'reviews' => 67,
        'badge' => 'NEW',
        'desc' => '밤의 신비로움을 담은 관능적인 향기입니다.',
    ],
    [
        'id' => 7,
        'name' => 'Citrus Burst',
        'type' => '바디미스트',
        'price' => 55000,
        'originalPrice' => 68000,
        'rating' => 4.4,
        'reviews' => 145,
        'badge' => 'SALE',
        'desc' => '상큼한 시트러스가 톡톡 터지는 활기찬 향기입니다.',
    ],
    [
        'id' => 8,
        'name' => 'Soft Cotton',
        'type' => '섬유유연제',
        'price' => 28000,
        'originalPrice' => null,
        'rating' => 4.7,
        'reviews' => 312,
        'badge' => 'BEST',
        'desc' => '갓 세탁한 면처럼 포근하고 깨끗한 향기입니다.',
    ],
];

// id로 상품 하나 찾는 헬퍼 함수
function find_product_by_id($id)
{
    global $products;
    foreach ($products as $p) {
        if ((int)$p['id'] === (int)$id) {
            return $p;
        }
    }
    return null;
}

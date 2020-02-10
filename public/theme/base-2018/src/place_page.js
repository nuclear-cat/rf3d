import $ from "jquery";

$('.place-slider').slick({
    dots: true,
    adaptiveHeight: true,
    asNavFor: '.place-slider-nav'
});

$('.place-slider-nav').slick({
    slidesToShow: 4,

    slidesToScroll: 1,
    asNavFor: '.place-slider',
    dots: false,
    // centerMode: true,
    focusOnSelect: true
});
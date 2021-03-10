import $ from "jquery";

$('.place-slider').slick({
    lazyLoad: 'progressive',
    dots: false,
    adaptiveHeight: false,
    asNavFor: '.place-slider-nav',
});

$('.place-slider-nav').slick({
    slidesToShow: 4,
    slidesToScroll: 1,
    asNavFor: '.place-slider',
    dots: false,
    focusOnSelect: true,
    variableWidth: true,
});
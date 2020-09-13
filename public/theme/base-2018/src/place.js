$('.js-rooms-slider').slick({
    slidesToShow: 4,
    slidesToScroll: 1,
    centerPadding: '40px',
    dots: false,
    // centerMode: true,
    focusOnSelect: true,
    lazyLoad: 'progressive',
    responsive: [
    {
        breakpoint: 1024,
        settings: {
            slidesToShow: 3,
            slidesToScroll: 3,
            infinite: true,
            dots: true
        }
    },
    {
        breakpoint: 600,
        settings: {
            slidesToShow: 2,
            slidesToScroll: 1,
            dots: true,
        }
    },
    {
        breakpoint: 480,
        settings: {
            slidesToShow: 2,
            slidesToScroll: 1,
            dots: true,
        }
    }]
});
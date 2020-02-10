
document.addEventListener('DOMContentLoaded', function () {

    var $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

    if ($navbarBurgers.length > 0) {

        $navbarBurgers.forEach(function ($el) {
            $el.addEventListener('click', function () {

                // Get the target from the "data-target" attribute
                var target = $el.dataset.target;
                var $target = document.getElementById(target);

                // Toggle the class on both the "navbar-burger" and the "navbar-menu"
                $el.classList.toggle('is-active');
                $target.classList.toggle('is-active');

            });
        });
    }
    baguetteBox.run('.container');

    normalizeBrightness();

    document.querySelector('.notification > button.delete').addEventListener('click', function(e) {
        e.target.parentElement.style.display = 'none';
    }, false);
});



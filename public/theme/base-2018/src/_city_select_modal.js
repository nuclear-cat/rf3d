import BulmaModal from './_modal.js'

let citiesModalButton = document.querySelector("#citiesModalButton")
let citiesModal = new BulmaModal("#citiesModal")
citiesModalButton.addEventListener("click", function () {
    citiesModal.show()
})
citiesModal.addEventListener('modal:show', function () {
    console.log("opened")
})
citiesModal.addEventListener("modal:close", function () {
    console.log("closed")
})

const citiesModalFilterField = document.getElementById('citiesModalFilterField');

const filterCities = function (event) {
    let txtValue = '';
    let filter = event.target.value.toLowerCase();
    let modalBody = document.getElementById('citiesModalBody');
    let listItem = modalBody.getElementsByClassName('js-cities-modal-list-item');

    for (let i = 0; i < listItem.length; i++) {
        let a = listItem[i].getElementsByTagName('a')[0];
        txtValue = a.textContent || a.innerText;

        if (txtValue.toLowerCase().indexOf(filter) > -1) {
            listItem[i].style.display = '';
        } else {
            listItem[i].style.display = 'none';
        }
    }
}

citiesModalFilterField.addEventListener('input', filterCities);
citiesModalFilterField.addEventListener('propertychange', filterCities); // for IE8
const blocks = document.querySelector('.row-cols-xl-4').querySelectorAll('.col')

function setCookie(name, value, options = {}) {

    options = {
        path: '/',
        // при необходимости добавьте другие значения по умолчанию
        ...options
    };

    if (options.expires instanceof Date) {
        options.expires = options.expires.toUTCString();
    }

    let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

    for (let optionKey in options) {
        updatedCookie += "; " + optionKey;
        let optionValue = options[optionKey];
        if (optionValue !== true) {
            updatedCookie += "=" + optionValue;
        }
    }

    document.cookie = updatedCookie;
}

async function getJson(url = '/prices.json', data = {}) {
    const response = await fetch(url, {
        method: 'GET',
        mode: 'cors',
        cache: 'no-cache',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json'
        },
        redirect: 'follow',
        referrerPolicy: 'no-referrer',
    });
    return response.json();
}

getJson().then(data => {
    addListeners(data);
});

let selected_selected_goods = {}

function addListeners(data) {
    for (let i = 0; i < blocks.length; i++) {
        let amount = blocks[i].children[0].children[0].querySelector("h1");
        let minus = blocks[i].children[0].children[2].children[0];
        let plus = blocks[i].children[0].children[2].children[2];
        let price = blocks[i].children[0].children[2].children[1];
        let image = blocks[i].children[0].children[1].children[0].src;
        let id = blocks[i].children[0].children[1].children[0].id;

        let buyBtn = blocks[i].children[0].children[0].querySelector("button");
        buyBtn.addEventListener('click', () => {


            setCookie(id, amount.innerText.toString().slice(0, -3) + "_" + price.innerText.toString().slice(0, -1),
                {
                    secure: true,
                    'max-age': 3600,
                    'SameSite': "strict"
                });

            buyBtn.style.color = '#3d3c3c';
        });


        minus.addEventListener('click', () => {
            if (!((Number(amount.innerText.toString().slice(0, -3))) - data[id]['amount'] <= 0)) {
                amount.innerText = (Number(amount.innerText.toString().slice(0, -3)) - data[id]['amount']) + "шт.";
                price.innerText = (Number(price.innerText.toString().slice(0, -1)) - data[id]['price']) + "р";
            }
        });

        plus.addEventListener('click', () => {
            if (!((Number(amount.innerText.toString().slice(0, -3))) + data[id]['amount'] > data[id]['limit'])) {
                amount.innerText = (Number(amount.innerText.toString().slice(0, -3)) + data[id]['amount']) + "шт.";
                price.innerText = (Number(price.innerText.toString().slice(0, -1)) + data[id]['price']) + "р";
            }
        });
    }

}

function buy_benefit(benefit) {
    switch (benefit) {
        case "hero":
            setCookie(benefit, "1_90",
                {
                    secure: true,
                    'max-age': 3600,
                    'SameSite': "strict"
                });
            break;
        case "warlord":
            setCookie(benefit, "1_400",
                {
                    secure: true,
                    'max-age': 3600,
                    'SameSite': "strict"
                });
            break;
        case "eternal":
            setCookie(benefit, "1_900",
                {
                    secure: true,
                    'max-age': 3600,
                    'SameSite': "strict"
                });
            break;
        case "eternal_plus":
            setCookie(benefit, "1_4900",
                {
                    secure: true,
                    'max-age': 3600,
                    'SameSite': "strict"
                });
            break;
    }

    window.location.href = '/order.html';
}
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

function deleteCookie(name) {
    setCookie(name, "", {
        'max-age': -1
    })
}

function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? decodeURIComponent(matches[1]) : undefined;
}

const cookies = document.cookie.split(';');
const tbody = document.querySelector('tbody');
const flag = cookies[0] === "";
let overall_sum = 0;

async function test() {
    return await fetch('/prices.json').then(response => response.json());
}

let json =
    async () => {
        let data = await test();
        cookies.forEach(value => {
            if (!flag) {
                let cookie = value.split('=');
                if (cookie[0][0] === " ") cookie[0] = cookie[0].substring(1);
                if (!data[cookie[0]]) return;
                if (!cookie[1].includes('_')) return;
                let tr = document.createElement('tr');
                tr.setAttribute("id", cookie[0]);
                let th = document.createElement('th');
                th.setAttribute("scope", "row");
                th.classList.add('border-0');
                let div = document.createElement('th');
                div.classList.add('p-2');
                let img = document.createElement('img');
                img.src = "/assets/shop-items/gold_coin.png";
                img.setAttribute("width", "70");
                img.classList.add("img-fluid");
                img.classList.add("rounded");
                img.classList.add("shadow-sm");
                let div2 = document.createElement('div');
                div2.classList.add("ml-3");
                div2.classList.add("d-inline-block");
                div2.classList.add("align-middle");
                let h5 = document.createElement('h5');
                h5.classList.add("mb-0");
                let a = document.createElement('a');
                a.classList.add("text-dark");
                a.classList.add("d-inline-block");
                a.classList.add("align-middle");
                a.innerText = cookie[0];


                let td = document.createElement('td');
                td.classList.add("border-0");
                td.classList.add("align-middle");
                td.innerHTML = "<strong>" + cookie[1].split('_')[1] + "</strong>";
                overall_sum += Number(cookie[1].split('_')[1]);

                let td2 = document.createElement('td');
                td2.classList.add("border-0");
                td2.classList.add("align-middle");
                td2.innerHTML = "<strong>" + cookie[1].split('_')[0] + "</strong>";

                let td3 = document.createElement('td');
                td3.classList.add("border-0");
                td3.classList.add("align-middle");
                td3.innerHTML = "<i class=\"fa fa-trash\"></i>";

                td3.addEventListener('click', ev => {
                    if (cookie[0][0] === " ") {
                        deleteCookie(cookie[0].slice(1));
                    }
                    if (typeof getCookie(cookie[0]) !== undefined) {
                        deleteCookie(cookie[0]);
                    }
                    document.getElementById(cookie[0]).remove();
                    document.getElementById("total").innerText = (Number(document.getElementById("total").innerText.slice(0,-2)) -
                        Number(cookie[1].split('_')[1])) + "Р."
                });
                h5.appendChild(a);
                div2.appendChild(h5);

                tr.appendChild(th);
                tr.appendChild(td);
                tr.appendChild(td2);
                tr.appendChild(td3);

                th.appendChild(div);
                div.appendChild(div2);
                tbody.appendChild(tr);
            }
        });
        document.getElementById("total").innerText = overall_sum.toString() + "Р."
    }
json();
let dateInput = document.getElementById("dateInput")

dateInput.addEventListener("input", () => {
    document.getElementById("timeInput").removeAttribute('readonly')
    document.getElementById("durationInput").removeAttribute('readonly')

    document.getElementById("tablePlaceholder").removeAttribute('selected')
    document.getElementById("tablePlaceholder").text = 'Выберите стол'
    document.getElementById("tablePlaceholder").setAttribute('selected', true)

    document.querySelectorAll(".tableOption").forEach(e => e.removeAttribute('disabled'))

    document.getElementById("durationInput").removeAttribute('disabled')
    document.getElementById("checkBtn").removeAttribute('disabled')

    document.getElementById("checkBtnLbl").innerHTML = 'Показать доступное время'
})

let durationInput = document.getElementById("durationInput")

durationInput.addEventListener("input", () => {
    document.getElementById("durationLabel").innerHTML = convertDuration(durationInput.value)

    function convertDuration(duration) {
        map = [
            "никаких часов тут нет",
            "полчаса",
            "час",
            "полтора часа",
            "два часа"
        ]

        return map[duration]
    }
})

let timeDisplay = document.getElementById("possibleTime")
let checkBtn = document.getElementById("checkBtn")

checkBtn.addEventListener("click", () => {
    let dateNumber = (dateInput.value).replace(/-/g, "")
    httpGetAsync("/getTimeJS/" + tableInput.value + "/" + dateNumber, (json) => {
        possibleTimeArray = []
        try { possibleTimeArray = JSON.parse(json) } catch (e) { console.log(e) }
        document.getElementById("possibleTime").innerHTML = ''
        for (let i = 0; i < possibleTimeArray.length; i++) {
            document.getElementById("possibleTime").innerHTML += "<strong>" + possibleTimeArray[i] + "</strong><br>"
        }
    })
})

function httpGetAsync(theUrl, callback) {
    let xmlHttp = new XMLHttpRequest()
    xmlHttp.onreadystatechange = function () {
        if (xmlHttp.readyState == 4 && xmlHttp.status == 200)
            callback(xmlHttp.responseText);
    }
    xmlHttp.open("GET", theUrl, true); // true for asynchronous 
    xmlHttp.send(null);
}

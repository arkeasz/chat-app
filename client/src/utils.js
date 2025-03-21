function parseMessage(message) {
    let msg = { type: "", sender: "", text: "" };
    try {
        msg = JSON.parse(message);
    } catch (e) {
        return false;
    }

    return msg;
}

function appendMessage(msg) {
    const msgContainer = document.querySelector(".messages");
    let parsedMsg;

    if (parsedMsg = parseMessage(msg)) {
        console.log("appending message ...\n", parsedMsg);
        let msgEle, senderEle, textEle;
        let sender, txt;

        msgEle = document.createElement("div");
        msgEle.classList.add("msg");
        msgEle.classList.add("msg-" + parsedMsg.type);

        senderEle = document.createElement("span");
        senderEle.classList.add("msg-sender");

        textEle = document.createElement("span");
        textEle.classList.add("msg-text");
        if (parsedMsg.type === "join") {
            txt = document.createTextNode(parsedMsg.text);
        } else {
            sender = document.createTextNode(parsedMsg.sender + ": ");
            senderEle.appendChild(sender);
            txt = document.createTextNode(parsedMsg.text);
        }

        textEle.appendChild(txt);

        msgEle.appendChild(senderEle);
        msgEle.appendChild(textEle);

        msgContainer.appendChild(msgEle);
    }
}


export { parseMessage, appendMessage };

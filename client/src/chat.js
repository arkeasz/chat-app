import { appendMessage } from "./utils";

let socket = new WebSocket("ws://localhost:8020");

function setup() {
    let sender = "";
    let joinForm = document.querySelector(".join-form");
    let msgForm = document.querySelector(".message-form");
    let leaveForm = document.querySelector(".leave-form");

    const joinFormEvent = (e) => {
        e.preventDefault();
        sender = document.getElementById("sender").value;
        let joinMsg = {
            type: "join",
            sender: sender,
            text: sender + " joined the chat"
        };

        socket.send(JSON.stringify(joinMsg));
        joinForm.classList.add("hidden");
        msgForm.classList.remove("hidden");
        leaveForm.classList.remove("hidden");
    }

    const leaveFormEvent = (e) => {
        e.preventDefault();
        socket.close();
        window.location.reload();
    }

    const msgFormEvent = (e) => {
        e.preventDefault();

        let msgField, msgText, msg;
        msgField = document.getElementById("message");
        msgText = msgField.value;
        msg = {
            type: "normal",
            sender: sender,
            text: msgText
        };

        socket.send(JSON.stringify(msg));
        msgField.value = "";
    }

    joinForm.addEventListener("submit", joinFormEvent);
    leaveForm.addEventListener("submit", leaveFormEvent);
    msgForm.addEventListener("submit", msgFormEvent);
}

let socketOpen = (e) => {
    console.log("connected to server");

    let msg = {
        type: "message",
        sender: "Browser",
        text: "connected to chat server",
    };

    appendMessage(JSON.stringify(msg));
    setup();
}

let socketMessage = (e) => {
    console.log("received message: ", e.data);
    appendMessage(e.data);
}

let socketClose = (e) => {

}

let socketError = (e) => {
}

socket.addEventListener("open", socketOpen);
socket.addEventListener("message", socketMessage);
socket.addEventListener("close", socketClose);
socket.addEventListener("error", socketError);

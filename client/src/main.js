import './style.css'
import './chat';
document.querySelector('#app').innerHTML = `
  <div class="container">
    <div class="chat-box">
      <div class="messages"></div>
      <form action="" class="join-form">
        <input type="text" name="sender" id="sender" placeholder="Enter name" />
        <button type="submit">Join Chat</button>
      </form>
      <form action="" class="message-form hidden">
        <input type="text" name="message" id="message" placeholder="Enter message" />
        <button type="submit">Send</button>
      </form>
      <form action="" class="leave-form hidden">
        <button type="submit">Leave Chat</button>
      </form>
    </div>
  </div>
`

const userButton = document.getElementById("userButton");
const dropdownMenu = document.getElementById("dropdownMenu");

userButton.addEventListener("click", () => {
  dropdownMenu.classList.toggle("show");
});

window.addEventListener("click", (e) => {
  if (!userButton.contains(e.target)) {
    dropdownMenu.classList.remove("show");
  }
});

const cerrarChat = document.getElementById("cerrarChat");
const chatbot = document.getElementById("chatbot");

cerrarChat.addEventListener("click", () => {
  chatbot.style.display = "none";
});
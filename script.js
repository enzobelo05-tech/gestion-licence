const addInstructor = document.querySelector(".addInstructor");
const popUp = document.querySelector(".popUp");
const popUpCloseBtn = document.querySelector(".close-btn-popUp");
const popUpCancelBtn = document.querySelector(".cancel-btn-popUp");
const rightPage = document.querySelector(".right-page");
const aside = document.querySelector(".aside-page");
const overlay = document.querySelector(".overlay");

if (addInstructor) {
  addInstructor.addEventListener("click", () => {
    popUp.style.display = "flex";
    overlay.style.display = "block";
  });
}
if (popUpCloseBtn) {
  popUpCloseBtn.addEventListener("click", () => {
    popUp.style.display = "none";
    overlay.style.display = "none";
  });
}
if (popUpCancelBtn) {
  popUpCancelBtn.addEventListener("click", () => {
    popUp.style.display = "none";
    overlay.style.display = "none";
  });
}

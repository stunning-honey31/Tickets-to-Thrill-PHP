/* =========================================================
   TICKETS TO THRILL — client-side UI helpers
   All cart, auth and booking logic now lives server-side in PHP.
   This file only handles small progressive-enhancement bits:
   the nav hamburger and the "how many do you want" qty stepper
   on the Tickets page (cart page qty steppers are plain form
   submits so they work with JS off too).
   ========================================================= */

document.addEventListener("DOMContentLoaded", () => {
  // mobile nav toggle
  const burger = document.querySelector(".hamburger");
  const links = document.querySelector(".nav-links");
  if (burger && links) {
    burger.addEventListener("click", () => links.classList.toggle("open"));
  }

  // qty stepper on the Tickets page: adjusts a hidden input's value
  // before the "Add to Cart" form is submitted.
  document.querySelectorAll("[data-qty]").forEach((stepper) => {
    const input = stepper.querySelector("input");
    stepper.querySelectorAll("button[data-step]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const step = parseInt(btn.dataset.step, 10);
        const val = Math.max(1, parseInt(input.value, 10) + step);
        input.value = val;
      });
    });
  });
});

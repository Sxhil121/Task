function validateForm() {
  let errors = [];

  const name = document.forms["userForm"]["name"].value;
  const email = document.forms["userForm"]["email"].value;
  const mobile = document.forms["userForm"]["mobile"].value;
  const gender = document.forms["userForm"]["gender"].value;
  const experiences = document.querySelectorAll(".experience");

  if (name === "") {
    errors.push("Name is required");
  }

  if (email === "" || !validateEmail(email)) {
    errors.push("Valid email is required");
  }

  if (mobile === "" || !/^[0-9]{10,15}$/.test(mobile)) {
    errors.push("Valid mobile number is required");
  }

  if (gender === "") {
    errors.push("Gender is required");
  }

  experiences.forEach((exp) => {
    if (
      exp.querySelector(".company").value === "" ||
      exp.querySelector(".years").value === "" ||
      exp.querySelector(".months").value === ""
    ) {
      errors.push("All experience fields are required");
    }
  });

  if (errors.length > 0) {
    alert(errors.join("\n"));
    return false;
  }

  return true;
}

function validateEmail(email) {
  const re =
    /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\\.,;:\s@\"]+\.)+[^<>()[\]\\.,;:\s@\"]{2,})$/i;
  return re.test(String(email).toLowerCase());
}

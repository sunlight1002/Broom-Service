import React, { useEffect, useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import User from '../../Assets/image/user.png';
import { useAlert } from "react-alert";
import WorkerMobileHeader from "./WorkerMobileHeader";
import i18next from "i18next";
import { useTranslation } from "react-i18next";
export default function WorkerHeader() {
  const alert = useAlert();
  const navigate = useNavigate();
  const [avatar, setAvatar] = useState("");
  const [fullName, setFullName] = useState("");
  const { t } = useTranslation();

  const lng = localStorage.getItem("worker-lng");
  const HandleLogout = (e) => {
    fetch("/api/logout", {
      method: "POST",
      headers: {
        "Accept": "application/json, text/plain, */*",
        "Content-Type": "application/json",
        "Authorization": `Bearer ` + localStorage.getItem("worker-token"),
      },
    }).then((res) => {
      if (res.status === 200) {
        localStorage.removeItem("worker-token");
        localStorage.removeItem("worker-name");
        localStorage.removeItem("worker-id");
        localStorage.removeItem("worker-lng");
        navigate("/worker/login");
        alert.success(t("global.Logout"));
      }
    });
  };


// Only proceed if language is Hebrew
if (lng === "en") {
  const allElements = document.querySelectorAll('*');
  allElements.forEach(element => {
    const dirAttr = element.getAttribute('dir');
    const computedDirection = window.getComputedStyle(element).direction;

    if (dirAttr || computedDirection === 'rtl' || computedDirection === 'ltr') {

      // If computed direction is 'rtl', remove it
      if (computedDirection === 'rtl') {
        if (dirAttr) {
          element.removeAttribute('dir');
        }

        // Option 2: If there's inline style, reset the direction
        element.style.removeProperty('direction');
      }
    }
  });
}
  

  const resetRTL = () => {
    // Remove `dir="rtl"` from the <html> tag
    const htmlElement = document.querySelector("html");
    if (htmlElement.getAttribute("dir") === "rtl") {
      htmlElement.setAttribute("dir", "ltr");
    }

    // Remove any existing RTL-specific styles
    const rtlLink = document.querySelector('link[href*="rtl.css"]');
    if (rtlLink) rtlLink.remove();

    // Reset affected inline styles or inherited styles
    document.body.style.textAlign = ""; // Example for body text alignment
    const rtlElements = document.querySelectorAll("[dir='rtl']");
    rtlElements.forEach((el) => el.removeAttribute("dir"));
  };

  const getAvatar = () => {
    axios
      .get('/api/details', {
        headers: {
          "Accept": "application/json, text/plain, */*",
          "Content-Type": "application/json",
          "Authorization": `Bearer ` + localStorage.getItem("worker-token"),
        }
      })
      .then((res) => {
        const lang = res.data.success.lng;
        setFullName(res.data?.success?.firstname + " " + res.data?.success?.lastname);
        i18next.changeLanguage(lang);

        if (lang === "heb") {
          import("../../Assets/css/rtl.css").then(() => {
            document.querySelector("html").setAttribute("dir", "rtl");
          });
        } else {
          resetRTL(); // Call resetRTL function to clean up
        }
      });
  };

  useEffect(() => {
    if (lng === "heb") {
      import("../../Assets/css/rtl.css").then(() => {
        document.querySelector("html").setAttribute("dir", "rtl");
      });
    } else {
      resetRTL(); // Reset styles and direction
    }
  }, [lng]);




  useEffect(() => {
    getAvatar();
  }, []);

  return (
    <>
      <div className='AdminHeader hidden-xs'>
        <div className="container-fluid">
          <div className="row">
            <div className="col-sm-6">
              <h1>{t('worker.welcome')} {fullName}</h1>
            </div>
            <div className="col-sm-6">
              <div className="float-right">
                <div className="dropdown show">
                  <Link className="dropdown-toggle" href="#!" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <img src={User} className='img-fluid' alt='Ajay' />
                  </Link>
                  <div className="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuLink">
                    <Link className="dropdown-item" to="/worker/my-account">{t('worker.my_account')}</Link>
                    <Link className="dropdown-item" onClick={HandleLogout}>{t('worker.logout')}</Link>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <WorkerMobileHeader />
    </>
  )
}

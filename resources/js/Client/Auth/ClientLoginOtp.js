import React, { useEffect, useState, useRef } from 'react';
import logo from "../../Assets/image/sample.svg";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import i18next from "i18next";
import { useNavigate } from 'react-router-dom';

export default function ClientLoginOtp() {
    const [otp, setOtp] = useState(new Array(6).fill(""));
    const inputsRef = useRef([]);
    const buttonRef = useRef(null);
    const alert = useAlert();
    const [errors, setErrors] = useState([]);
    const [dir, setDir] = useState([]);
    const navigate = useNavigate();
    const [timer, setTimer] = useState(60); // 1 minutes in seconds
    const [canResend, setCanResend] = useState(false);
    const { t } = useTranslation()
    const [remind, setRemind] = useState(false)


    const clientEmail = localStorage.getItem("client-email");
    const clientLng = localStorage.getItem("client-lng")

    useEffect(() => {
        i18next.changeLanguage(clientLng);
        if(clientLng == "en") {
            document.querySelector("html").removeAttribute("dir");
                const rtlLink = document.querySelector('link[href*="rtl.css"]');
                if (rtlLink) {
                    rtlLink.remove();
                }
        }
    }, [clientLng]);

    useEffect(() => {
        const clientLogin = localStorage.getItem("client-id");
        if (clientLogin) {
            navigate("/client/dashboard");
        }
    }, [navigate]);

    useEffect(() => {
        const countdown = setInterval(() => {
            setTimer(prevTimer => {
                if (prevTimer > 0) {
                    return prevTimer - 1;
                } else {
                    clearInterval(countdown);
                    setCanResend(true);
                    return 0;
                }
            });
        }, 1000);

        return () => clearInterval(countdown);
    }, [canResend]);

    useEffect(() => {
        inputsRef.current[0].focus();
        buttonRef.current.setAttribute("disabled", "disabled");
    }, []);

    const handleResend = async (e) => {
        e.preventDefault();

        const data = {
            email: clientEmail
        };

        try {
            const result = await axios.post(`/api/client/resendOtp`, data);
            if (result.data.errors) {
                setErrors(result.data.errors.otp);
                console.log(errors);
            } else {
                console.log(result.data.message);
                alert.success(result.data.message);

                setTimer(60);
                setCanResend(false);
                const countdown = setInterval(() => {
                    setTimer(prevTimer => {
                        if (prevTimer > 0) {
                            return prevTimer - 1;
                        } else {
                            clearInterval(countdown);
                            setCanResend(true);
                            return 0;
                        }
                    });
                }, 1000);
            }
        } catch (error) {
            console.error("Error verifying OTP:", error);
        }
    };

    const HandleLogin = async (e) => {
        e.preventDefault();

        const stringOtp = otp.join(""); // Join array into a single string
        const data = {
            otp: stringOtp,
            remember_device: remind
        };

        try {
            const result = await axios.post(`/api/client/verifyOtp`, data);
            if (result.data.errors) {
                setErrors(result.data.errors.otp);
            } else {
                localStorage.setItem("client-token", result.data.client.token);
                i18next.changeLanguage(result.data.client.lng);
                if(result?.data?.client?.lng == "en") {
                    document.querySelector("html").removeAttribute("dir");
                        const rtlLink = document.querySelector('link[href*="rtl.css"]');
                        if (rtlLink) {
                            rtlLink.remove();
                        }
                }
                localStorage.setItem(
                    "client-name",
                    result.data.client.firstname + " " + result.data.client.lastname
                );
                localStorage.setItem("client-id", result.data.client.id);

                const rememberToken = result?.data?.remember_token;

                if (rememberToken) {
                    document.cookie = `remember_device_token=${rememberToken}; path=/; max-age=2592000`; // 30 days
                }

                window.location = "/client/dashboard";
            }
        } catch (error) {
            console.error("Error verifying OTP:", error);
        }
    };

    const handlePaste = (event) => {
        event.preventDefault();
        const pastedValue = (event.clipboardData || window.clipboardData).getData("text");
        const otpLength = otp.length;

        const newOtp = [...otp];
        for (let i = 0; i < otpLength; i++) {
            if (i < pastedValue.length) {
                newOtp[i] = pastedValue[i];
            } else {
                newOtp[i] = ""; // Clear any remaining inputs
            }
        }
        setOtp(newOtp);
        focusNextEmptyInput(newOtp);
    };

    const handleChange = (e, index) => {
        const { value } = e.target;
        if (value.length > 1) {
            return;
        }

        const newOtp = [...otp];
        newOtp[index] = value;
        setOtp(newOtp);

        if (value !== "" && index < otp.length - 1) {
            inputsRef.current[index + 1].removeAttribute("disabled");
            inputsRef.current[index + 1].focus();
        }

        validateOtp(newOtp);
    };

    const handleKeyDown = (e, index) => {
        if (e.key === "Backspace" && index > 0) {
            const newOtp = [...otp];
            newOtp[index] = "";
            setOtp(newOtp);
            inputsRef.current[index - 1].focus();
            inputsRef.current[index].setAttribute("disabled", true);
        }
    };

    const validateOtp = (newOtp) => {
        if (newOtp.every(value => value !== "")) {
            buttonRef.current.removeAttribute("disabled");
            buttonRef.current.classList.add("active");
        } else {
            buttonRef.current.setAttribute("disabled", "disabled");
            buttonRef.current.classList.remove("active");
        }
    };

    const focusNextEmptyInput = (newOtp) => {
        for (let i = 0; i < newOtp.length; i++) {
            if (newOtp[i] === "") {
                inputsRef.current[i].focus();
                break;
            }
        }
    };

    useEffect(() => {
        let d = document.querySelector("html").getAttribute("dir");
        d === "rtl" ? setDir("heb") : setDir("en");
    }, []);

    return (
        <div id="loginPage">
            <div className="container adminLogin">
                <div className="formSide">
                    <div className="hidden-xs text-center ifRTL">
                        <svg
                            width="333"
                            height="135"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlnsXlink="http://www.w3.org/1999/xlink"
                        >
                            <image
                                xlinkHref={logo}
                                width="333"
                                height="135"
                            ></image>
                        </svg>
                    </div>
                    <div className="hidden-xl ifRTL text-center">
                        <svg
                            width="250"
                            height="94"
                            xmlns="http://www.w3.org/2000/svg"
                            xmlnsXlink="http://www.w3.org/1999/xlink"
                        >
                            <image
                                xlinkHref={logo}
                                width="250"
                                height="94"
                            ></image>
                        </svg>
                    </div>
                    <h1 className="page-title text-center">
                        {dir === "heb" ? "כניסת לקוחות" : "Client Login otp"}
                    </h1>
                    <form onSubmit={HandleLogin}>
                        <div className="form-group">
                            <div className="container-fluid bg-body-tertiary d-block">
                                <div className="row justify-content-center">
                                    <div className="col-12 col-md-6 col-lg-4" style={{ minWidth: "500px" }}>
                                        <div className="card bg-white mb-5 border-0" style={{ boxShadow: "none" }}>
                                            <div className="card-body text-center">
                                                <h4>{t("resendOtp.verify")}</h4>
                                                <p>{t("resendOtp.subtitle")}</p>

                                                <div className="otp-field mb-4" onPaste={handlePaste}>
                                                    {otp.map((value, index) => (
                                                        <input
                                                            key={index}
                                                            type="number"
                                                            name='otp'
                                                            value={value}
                                                            onChange={(e) => handleChange(e, index)}
                                                            onKeyDown={(e) => handleKeyDown(e, index)}
                                                            ref={(el) => (inputsRef.current[index] = el)}
                                                            disabled={index !== 0 && otp[index - 1] === ""}
                                                        />
                                                    ))}
                                                </div>
                                                <button type='submit' className="btn btn-danger mb-3" ref={buttonRef}>
                                                    {t("resendOtp.verify")}
                                                </button>

                                                {canResend ? (
                                                    <p className="resend text-muted mb-0">
                                                        {t("resendOtp.receiveCode")} <a href="" onClick={handleResend}>{t("resendOtp.requestAgain")}</a>
                                                    </p>
                                                ) : (
                                                    <p className="resend text-muted mb-0">
                                                        {t("resendOtp.resendAvailableIn")} {Math.floor(timer / 60)}:{String(timer % 60).padStart(2, '0')}
                                                    </p>
                                                )}
                                            </div>
                                            {errors && (
                                                <small className="text-danger text-center mb-1">
                                                    {errors}
                                                </small>
                                            )}
                                            <div className='d-flex justify-content-center align-items-center'>
                                                <label htmlFor='remind'
                                                    style={{ marginBottom: "0", marginRight: "5px" }}
                                                >Remember this device</label>
                                                <input onChange={e => setRemind(prev => !prev)} type='checkbox' id='remind' name='remember' />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {/* <div className="form-group mt-4">
                            <button
                                type="submit"
                                className="btn btn-danger btn-block"
                            >
                                Login
                            </button>
                        </div> */}
                    </form>
                </div>
            </div>
        </div>
    );
}

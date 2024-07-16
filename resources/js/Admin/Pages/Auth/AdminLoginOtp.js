import React, { useEffect, useState, useRef } from 'react';
import { useAlert } from "react-alert";
import logo from "../../../Assets/image/sample.svg";
import { useNavigate } from 'react-router-dom';

export default function AdminLoginOtp() {
    const navigate = useNavigate()
    const alert = useAlert();
    const [otp, setOtp] = useState(new Array(6).fill(""));
    const inputsRef = useRef([]);
    const buttonRef = useRef(null);
    const [errors, setErrors] = useState([]);


    useEffect(() => {
        const adminLogin = localStorage.getItem("admin-id")
        // console.log(adminLogin);
        if (adminLogin) {
            navigate("/admin/dashboard");
        }
    }, [navigate])

    const handleLogin = async (e) => {
        e.preventDefault();


        const stringOtp = otp.join(""); // Join array into a single string
        // let numOtp = Number(stringOtp);
        const data = {
            otp: stringOtp,
        };

        try {
            const result = await axios.post(`/api/admin/verifyOtp`, data);
            if (result.data.errors) {
                setErrors(result.data.errors.otp);
                console.log(errors);
            } else {
                localStorage.setItem("admin-token", result.data.token);
                localStorage.setItem("admin-name", result.data.name);
                localStorage.setItem("admin-id", result.data.id);
                window.location = "/admin/dashboard";
                // console.log(result);
            }
        } catch (error) {
            console.error("Error verifying OTP:", error);
        }
    };


    useEffect(() => {
        inputsRef.current[0].focus();
        buttonRef.current.setAttribute("disabled", "disabled");
    }, []);

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

    return (
        <div id="loginPage">
            <div className="container adminLogin">
                <div className="formSide pb-0">
                    <div className="hidden-xs ifRTL d-flex justify-content-center">
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
                    <div className="hidden-xl ifRTL">
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
                    <h1 className="page-title text-center">Admin Login Otp</h1>
                    <form onSubmit={handleLogin}>
                        <div className="form-group">
                            <div className="container-fluid bg-body-tertiary d-block">
                                <div className="row justify-content-center">
                                    <div className="col-12 col-md-6 col-lg-4" style={{ minWidth: "500px" }}>
                                        <div className="card bg-white mb-5 border-0" style={{ boxShadow: "none" }}>
                                            <div className="card-body text-center">
                                                <h4>Verify</h4>
                                                <p>Your code was sent to you via email or Sms</p>

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
                                                    Verify
                                                </button>

                                                <p className="resend text-muted mb-0">
                                                    Didn't receive code? <a href="">Request again</a>
                                                </p>
                                            </div>
                                                {errors && (
                                                    <small className="text-danger text-center mb-1">
                                                        {errors}
                                                    </small>
                                                )}
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

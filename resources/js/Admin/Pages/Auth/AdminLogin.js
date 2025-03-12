import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import logo from "../../../Assets/image/sample.svg";
import { useNavigate } from "react-router-dom";
import FullLoader from "../../../../../public/js/FullLoader";
import i18next from "i18next";
import { getCookie } from "../../Components/common/Cookies";
import FullPageLoader from "../../../Components/common/FullPageLoader";


export default function AdminLogin() {
    const navigate = useNavigate()

    useEffect(() => {
        const adminLogin = localStorage.getItem("admin-token")
        // console.log(adminLogin);
        if (adminLogin) {
            navigate("/admin/dashboard");
        }
    }, [navigate])

    const adminLng = localStorage.getItem("admin-lng")

    const alert = useAlert();
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [errors, setErrors] = useState([]);
    const [loading, setLoading] = useState(false)
    const [dir, setDir] = useState([])

    const [isRemembered, setIsRemembered] = useState(false);

    useEffect(() => {
        const token = getCookie('remember_device_token');
        if (token) {
            setIsRemembered(true);
        }
    }, []);


    const handleLogin = async (e) => {
        e.preventDefault();
        setLoading(true)

        const data = {
            email: email,
            password: password,
        };

        await axios.post(`/api/admin/login`, data).then((result) => {
            // console.log(result);

            if (result.data.errors) {
                setLoading(false)
                setErrors(result.data.errors);
            } else {
                if (isRemembered) {
                    localStorage.setItem("admin-token", result.data.token);
                    localStorage.setItem("admin-name", result.data.name);
                    localStorage.setItem("admin-id", result.data.id);
                    localStorage.setItem("admin-lng", result.data.lng);
                    const adminLng = localStorage.getItem("admin-lng")
                    i18next.changeLanguage(adminLng);
                    if (adminLng == "en") {
                        document.querySelector("html").removeAttribute("dir");
                        const rtlLink = document.querySelector('link[href*="rtl.css"]');
                        if (rtlLink) {
                            rtlLink.remove();
                        }
                    }
                    window.location = "/admin/dashboard";
                } else {
                    if (result.data.two_factor_enabled === 1 || result.data[0] === 1) {
                        localStorage.setItem("admin-email", result.data.email);
                        localStorage.setItem("admin-lng", result.data.lng);
                        setLoading(false)
                        window.location = "/admin/login-otp";
                    } else {
                        localStorage.setItem("admin-token", result.data.token);
                        localStorage.setItem("admin-name", result.data.name);
                        localStorage.setItem("admin-id", result.data.id);
                        localStorage.setItem("admin-lng", result.data.lng);
                        const adminLng = localStorage.getItem("admin-lng")
                        i18next.changeLanguage(adminLng);
                        if (adminLng == "en") {
                            document.querySelector("html").removeAttribute("dir");
                            const rtlLink = document.querySelector('link[href*="rtl.css"]');
                            if (rtlLink) {
                                rtlLink.remove();
                            }
                        }
                        window.location = "/admin/dashboard";
                    }
                }
            }
        });
    };

    const forgotPassword = async () => {
        if (!email) {
            alert.error('Please enter your email');
            return;
        }

        try {
            const response = await axios.post('/api/admin/password/email', { email });

            alert.success(response?.data?.message || 'Reset link sent! Check your email.');
        } catch (err) {
            alert.error(err.response?.data?.message || 'Failed to send reset link.');
            console.error(err);
        }
    };

    useEffect(() => {
        let d = document.querySelector("html").getAttribute("dir");
        d == "rtl" ? setDir("heb") : setDir("en");
    }, []);


    return (
        <div id="loginPage">
            <div className="container adminLogin">
                <div className="formSide">
                    <div className="hidden-xs ifRTL">
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
                    <h1 className="page-title">Admin Login</h1>
                    <form onSubmit={handleLogin}>
                        <div className="form-group">
                            <div className="input-group mt-2">
                                <div className="input-group-prepend">
                                    <span
                                        className="input-group-text"
                                        id="basic-addon1"
                                    >
                                        <i className="fa-solid fa-user"></i>
                                    </span>
                                </div>
                                <input
                                    type="email"
                                    className="form-control"
                                    onChange={(e) => setEmail(e.target.value)}
                                    placeholder="Username"
                                    name="username"
                                    aria-label="Username"
                                    autoFocus
                                />
                            </div>
                            {errors.email && (
                                <small className="text-danger mb-1">
                                    {errors.email}
                                </small>
                            )}
                        </div>
                        <div className="form-group">
                            <div className="input-group">
                                <div className="input-group-prepend">
                                    <span
                                        className="input-group-text"
                                        id="basic-addon1"
                                    >
                                        <i className="fa-solid fa-key"></i>
                                    </span>
                                </div>
                                <input
                                    type="password"
                                    className="form-control"
                                    onChange={(e) =>
                                        setPassword(e.target.value)
                                    }
                                    placeholder="Password"
                                    name="password"
                                    aria-label="Password"
                                />
                            </div>
                            {errors.password && (
                                <small className="text-danger mb-1">
                                    {errors.password}
                                </small>
                            )}
                        </div>
                        <div className='d-flex justify-content-start align-items-center'>
                            <button type="button" className="btn btn-link p-0" onClick={() => forgotPassword()}>forgot password</button>
                        </div>
                        <div className="form-group mt-4">
                            <button
                                type="submit"
                                className="btn btn-danger btn-block"
                            >
                                Login
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}

import i18next from "i18next";
import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useNavigate } from "react-router-dom";
import { getCookie } from "../../Admin/Components/common/Cookies";
import logo from "../../Assets/image/sample.svg";
import FullPageLoader from "../../Components/common/FullPageLoader";

export default function ClientLogin() {
    const [email, setEmail] = useState("");
    const [password, setPassword] = useState("");
    const [errors, setErrors] = useState([]);
    const [dir, setDir] = useState([]);
    const [loading, setLoading] = useState(false)
    const [isRemembered, setIsRemembered] = useState(false)
    const navigate = useNavigate()
    const alert = useAlert();

    useEffect(() => {
        const clientLogin = localStorage.getItem("client-token")
        // console.log(adminLogin);
        if (clientLogin) {
            navigate("/client/dashboard");
        }
    }, [navigate])


    useEffect(() => {
        const token = getCookie('remember_device_token');
        if (token) {
            setIsRemembered(true);
        }
    }, []);

    const forgotPassword = async () => {
        if (!email) {
            alert.error('Please enter email');
            return;
        }
        try {
          const response = await axios.post('/api/client/password/email', {
            email,
          });
          alert.success(response?.data?.message);

        } catch (err) {
            console.log(err);
            
        }
      };

    const HandleLogin = (e) => {
        e.preventDefault();
        setLoading(true);

        const data = {
            email: email,
            password: password,
        };
        axios.post(`/api/client/login`, data).then((result) => {
            if (result.data.errors) {
                setLoading(false);
                setErrors(result.data.errors);
                return;
            }

            const { token, lng, firstname, lastname, id, email, two_factor_enabled, first_login } = result.data;
            localStorage.setItem("client-id", id);

            const saveClientData = () => {
                localStorage.setItem("client-token", token);
                localStorage.setItem("client-name", `${firstname} ${lastname}`);
                i18next.changeLanguage(lng);

                if (lng === "en") {
                    document.querySelector("html").removeAttribute("dir");
                    const rtlLink = document.querySelector('link[href*="rtl.css"]');
                    if (rtlLink) rtlLink.remove();
                }
            };
            const redirectTo = (url) => {
                setLoading(false);
                window.location = url;
            };

            if (isRemembered) {
                if (first_login === 1) {
                    redirectTo("/client/change-password");
                } else {
                    saveClientData();
                    redirectTo("/client/dashboard");
                }
            } else {
                if (two_factor_enabled === 1 || result.data[0] === 1) {
                    localStorage.setItem("client-email", email);
                    localStorage.setItem("client-lng", lng);
                    redirectTo("/client/login-otp");
                } else {
                    if (first_login === 1) {
                        redirectTo("/client/change-password");
                    } else {
                        saveClientData();
                        redirectTo("/client/dashboard");
                    }
                }
            }
        });
    };

    useEffect(() => {
        let d = document.querySelector("html").getAttribute("dir");
        console.log(d);
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
                    <h1 className="page-title">
                        {dir == "heb" ? "כניסה ללקוח" : "Client Login"}
                    </h1>
                    <form onSubmit={HandleLogin}>
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
                                    placeholder="Email"
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
                                    autoComplete="new-password"
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

                        <div className="form-group mt-1">
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

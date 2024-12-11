import React, { useEffect, useState } from "react";
import logo from "../../Assets/image/sample.svg";
import i18next from "i18next";
import { useNavigate } from "react-router-dom";
import FullLoader from "../../../../public/js/FullLoader";
import { getCookie } from "../../Admin/Components/common/Cookies";
import FullPageLoader from "../../Components/common/FullPageLoader";


export default function WorkerLogin() {
    const [worker, setWorker] = useState("");
    const [password, setPassword] = useState("");
    const [errors, setErrors] = useState([]);
    const [loading, setLoading] = useState(false)
    const [isRemembered, setIsRemembered] = useState(false)

    const navigate = useNavigate()


    useEffect(() => {
        const workerLogin = localStorage.getItem("worker-token")
        // console.log(adminLogin);
        if (workerLogin) {
            navigate("/worker/dashboard");
        }
    }, [navigate])

    useEffect(() => {
        const token = getCookie('remember_device_token');
        if (token) {
            setIsRemembered(true);
        }
    }, []);

    const HandleLogin = (e) => {
        e.preventDefault();
        setLoading(true)
        const data = {
            worker_id: worker,
            password: password,
        };
        axios.post(`/api/login`, data).then((result) => {
            if (result.data.errors) {
                setLoading(false)
                setErrors(result.data.errors);
            } else {
                if (isRemembered) {
                    localStorage.setItem("worker-token", result.data.token);
                    i18next.changeLanguage(result.data.lng);
                    if(result?.data?.lng == 'en') {
                        document.querySelector("html").removeAttribute("dir");
                        const rtlLink = document.querySelector('link[href*="rtl.css"]');
                        if (rtlLink) {
                            rtlLink.remove();
                        }
                    }
                    localStorage.setItem(
                        "worker-name",
                        result.data.firstname + " " + result.data.lastname
                    );
                    localStorage.setItem("worker-id", result.data.id);

                    window.location = "/worker/dashboard";
                }else{
                    if (result.data.two_factor_enabled === 1 || result.data[0] === 1) {
                        localStorage.setItem("worker-email", result.data.email);
                        localStorage.setItem("worker-lng", result.data.lng);
                        window.location = "/worker/login-otp";
                        setLoading(false)
                    }else{
                        localStorage.setItem("worker-token", result.data.token);
                        i18next.changeLanguage(result.data.lng);
                        if(result?.data?.lng == 'en') {
                            document.querySelector("html").removeAttribute("dir");
                            const rtlLink = document.querySelector('link[href*="rtl.css"]');
                            if (rtlLink) {
                                rtlLink.remove();
                            }
                        }
                        localStorage.setItem(
                            "worker-name",
                            result.data.firstname + " " + result.data.lastname
                        );
                        localStorage.setItem("worker-id", result.data.id);

                        window.location = "/worker/dashboard";
                    }
                }
            }
        });
    };

    return (
        <div>
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
                        <h1 className="page-title">Worker Login</h1>
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
                                        type="text"
                                        name="email"
                                        className="form-control"
                                        placeholder="Enter Worker id or Email"
                                        onChange={(e) =>
                                            setWorker(e.target.value)
                                        }
                                        autoFocus
                                    />
                                </div>
                                {errors.worker && (
                                    <small className="text-danger mb-1">
                                        {errors.worker}
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
                                        name="password"
                                        className="form-control"
                                        placeholder="Enter password"
                                        onChange={(e) =>
                                            setPassword(e.target.value)
                                        }
                                        autoComplete="new-password"
                                    />
                                </div>
                                {errors.password && (
                                    <small className="text-danger mb-1">
                                        {errors.password}
                                    </small>
                                )}
                            </div>
                            <div className="form-group mt-4">
                                <button
                                    as="input"
                                    type="submit"
                                    className="btn btn-danger btn-block"
                                >
                                    {" "}
                                    Login
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            {loading && <FullPageLoader visible={loading} />}
        </div>
    );
}

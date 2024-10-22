import axios from "axios";
import React, { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import logo from "../Assets/image/sample.svg";
import { Base64 } from "js-base64";
import { useTranslation } from "react-i18next";

export const ExtraLinks = () => {
    const { id } = useParams();
    const [res, setRes] = useState('');
    const navigate = useNavigate();
    const params = new URLSearchParams(location.search);
    const type = params.get("q");
    const workerToken = localStorage.getItem("worker-token");
    const { t } = useTranslation();

    // if (!workerToken || workerToken == null) {
    //     navigate('/worker/')
    // }
    let headers;

    if(type == "extend"){
        headers = {
            Accept: "application/json, text/plain, */*",
            "Content-Type": "application/json",
            Authorization: `Bearer ` + localStorage.getItem("admin-token"),
        };
    }else{
        headers = {
            Accept: "application/json, text/plain, */*",
            "Content-Type": "application/json",
            Authorization: `Bearer ` + workerToken,
        };
        
    }

    const handleExtraTime = () => {
        axios
            .post(`/api/jobs/need-extra-time/${Base64.decode(id)}`, null, { headers })
            .then((res) => {
                console.log(res);
                setRes(res?.data?.message);
            })
            .catch((err) => {
                console.log(err);
            });
    };

    const handleExtendTime = () => {
        axios
            .post(`/api/user/jobs/need-extra-time/${Base64.decode(id)}`, null, { headers })
            .then((res) => {
                console.log(res);
                setRes(res?.data?.message);
            })
            .catch((err) => {
                console.log(err);
            });
    };

    useEffect(() => {
        if (type == "finish") {
            setRes(t("worker.thankyou"));
        }else if(type == "extend"){
            handleExtendTime();
        }else{
            handleExtraTime();
        }
    }, [t]);

    return (
        <div className="container">
            <div className="thankyou dashBox maxWidthControl p-4">
                <svg
                    width="190"
                    height="77"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlnsXlink="http://www.w3.org/1999/xlink"
                >
                    <image xlinkHref={logo} width="190" height="77"></image>
                </svg>
                <p className="text-center">{res || "Wait..."}</p>
            </div>
        </div>
    );
};

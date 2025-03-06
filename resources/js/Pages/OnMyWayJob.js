import React, { useState } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import { useParams } from "react-router-dom";
import logo from "../Assets/image/sample.svg";


export default function OnMyWayJob() {
    const alert = useAlert();
    const params = useParams();
    const { t } = useTranslation();
    const [isSubmitted, setIsSubmitted] = useState(false)
    const handleOpeningTime = (e) => {
        e.preventDefault();
        let data = {
            uuid: params.uuid,
        };
        axios
            .post(`/api/jobs/leave-for-Work`, data)
            .then((res) => {
                setIsSubmitted(true)
                alert.success(res.data.message);
            })
            .catch((err) => {
                alert.success(res.data.message);
            });
    };

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
                <div className="mt-4">
                    <button
                        type="button"
                        onClick={(e) => handleOpeningTime(e)}
                        disabled={isSubmitted}
                        className="btn btn-primary"
                    >
                        {t(
                            "worker.jobs.view.going_to_start"
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
}

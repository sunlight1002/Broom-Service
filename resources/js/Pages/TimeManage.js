import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams, useSearchParams } from "react-router-dom";
import logo from "../Assets/image/sample.svg";
import { Base64 } from "js-base64";
import Swal from "sweetalert2";

export const TimeManage = () => {
    const { id } = useParams();  
    const [searchParams] = useSearchParams(); // To read query parameters
    const [res, setRes] = useState('');
    const [job, setJob] = useState([]);
    const [action, setAction] = useState(""); // To store the action ("keep" or "adjust")

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    // Function to fetch job details
    const getJob = () => {
        axios
            .get(`/api/admin/jobs/${Base64.decode(id)}`, { headers })
            .then((res) => {
                const r = res.data.job;
                setJob(r);
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    // Function to handle the action (keep or adjust)
    const handleAction = () => {
        const selectedAction = searchParams.get("action");
        setAction(selectedAction);

        if (selectedAction === "keep" || selectedAction === "adjust") {
            axios
                .post(`/api/admin/jobs/${Base64.decode(id)}/adjust-time`, { action: selectedAction }, { headers })
                .then((response) => {
                    if (selectedAction === "keep") {
                        setRes("You have chosen to keep the actual time.");
                    } else if (selectedAction === "adjust") {
                        setRes("You have chosen to adjust the time to the scheduled time.");
                    }
                    Swal.fire({
                        title: "Success",
                        text: response.data.message,
                        icon: "success",
                    });
                })
                .catch((error) => {
                    Swal.fire({
                        title: "Error!",
                        text: error.response.data.message,
                        icon: "error",
                    });
                });
        } else {
            setRes("Invalid action or no action selected.");
        }
    };

    useEffect(() => {
        getJob();
        handleAction(); // Call the function to handle action based on query parameter
    }, [id, searchParams]);

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

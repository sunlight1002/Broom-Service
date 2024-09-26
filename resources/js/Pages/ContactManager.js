import axios from "axios";
import React, { useEffect, useState } from "react";
import { useParams } from "react-router-dom";
import logo from "../Assets/image/sample.svg";
import { Base64 } from "js-base64";

export const ContactManager = () => {
    const { id } = useParams();  // Get 'id' from the route parameters
    const [res, setRes] = useState('');

    // Setting up headers for the request
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };    

    // Function to handle the API call
    const handleContactManager = () => {
        axios
            .post(`/api/worker/contact-manager/${Base64.decode(id)}`, null, { headers })  // Post request with headers
            .then((res) => {
                console.log(res);  // Log the response for debugging
                setRes(res?.data?.message);  // Set the message from response in state
            })
            .catch((err) => {
                console.log(err);  // Log errors for debugging
            });
    };
    
    // Trigger the API call when the component mounts or the 'id' changes
    useEffect(() => {
        handleContactManager();
    }, [id]);

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
                <p className="text-center">{res || "Wait..."}</p>  {/* Display the API response message */}
            </div>
        </div>
    );
};

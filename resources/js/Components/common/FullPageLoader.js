import React from "react";
import "../../Assets/css/FullPageLoader.css";
import gif from "./3dot.gif"

const FullPageLoader = ({ visible = false }) => {
    return (
        visible && (
            // <div className="loader-container">
            //     <div className="loader"></div>
            // </div>
            <div className="loader-container">
                <div className="w-full">
                    <img src={gif} />
                </div>
            </div>
        )
    );
};

export default FullPageLoader;

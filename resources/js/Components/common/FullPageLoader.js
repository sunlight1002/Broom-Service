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
                    <img src={gif} />
            </div>
        )
    );
};

export default FullPageLoader;

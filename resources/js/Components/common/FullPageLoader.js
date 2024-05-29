import React from "react";
import "../../Assets/css/FullPageLoader.css";

const FullPageLoader = ({ visible = false }) => {
    return (
        visible && (
            <div className="loader-container">
                <div className="loader"></div>
            </div>
        )
    );
};

export default FullPageLoader;

import React, { useEffect, useState } from "react";
import "./CustomOffcanvas.css";

const CustomOffcanvas = ({ isOpen, handleClose, children }) => {
    useEffect(() => {
        if (isOpen) {
            document.body.classList.add("no-scroll");
        } else {
            document.body.classList.remove("no-scroll");
        }
    }, [isOpen]);

    return (
        <div>
            {isOpen && (
                <>
                    <div className={`offcanvas show`}>
                        <div className="offcanvas-header">
                            <button
                                onClick={handleClose}
                                className="close-button"
                            >
                                &times;
                            </button>
                        </div>
                        <div className="offcanvas-content">{children}</div>
                    </div>

                    <div className={`overlay show`} onClick={handleClose}></div>
                </>
            )}
        </div>
    );
};

export default CustomOffcanvas;

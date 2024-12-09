import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useNavigate, useLocation } from "react-router-dom";
import { useTranslation } from "react-i18next";
import axios from "axios";
import Swal from "sweetalert2";
import { RiDeleteBin6Line } from "react-icons/ri";
import { MdOutlineEdit } from "react-icons/md";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import { BsPinAngleFill } from "react-icons/bs";

export default function Card() {
    const { t } = useTranslation();
    const [cards, setCards] = useState([]);
    const [client, setClient] = useState([]);
    const [showPasswords, setShowPasswords] = useState({}); // Object to track visibility for each card
    const [isAddBtnDisabled, setIsAddBtnDisabled] = useState(false);
    const location = useLocation();
    const queryParams = new URLSearchParams(location.search);
    const navigate = useNavigate();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("client-token"),
    };

    useEffect(() => {
        const _paymentStatus = queryParams.get("cps");

        if (_paymentStatus === "payment-success") {
            Swal.fire(t("work-contract.messages.card_success"), "", "success");
            navigate(`/client/settings`);
        } else if (_paymentStatus === "payment-cancelled") {
            Swal.fire(t("work-contract.messages.card_adding_failed"), "", "error");
            navigate(`/client/settings`);
        }
    }, [queryParams]);

    const getCards = () => {
        axios.get(`/api/client/get-card`, { headers }).then((res) => {
            setCards(res.data.res);
        });
    };

    const handleCard = () => {
        setIsAddBtnDisabled(true);

        axios
            .post(`/api/client/cards/initialize-adding`, {}, { headers })
            .then((response) => {
                window.location.href = response.data.redirect_url;
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    useEffect(() => {
        getCards();
    }, []);

    const handleMarkDefault = (id) => {
        axios
            .put(`/api/client/cards/${id}/mark-default`, {}, { headers })
            .then(() => {
                Swal.fire(t("work-contract.messages.card_updated"), "", "success");
                getCards();
            })
            .catch((e) => {
                Swal.fire({
                    title: "Error!",
                    text: e.response.data.message,
                    icon: "error",
                });
            });
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: t("work-contract.areYouSure"),
            text: t("work-contract.willNotRevert"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: t("work-contract.yesDelete"),
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/client/cards/${id}`, { headers })
                    .then(() => {
                        Swal.fire(
                            t("global.deleted"),
                            t("work-contract.cardDeleted"),
                            "success"
                        );
                        getCards();
                    })
                    .catch((e) => {
                        Swal.fire({
                            title: "Error!",
                            text: e.response.data.message,
                            icon: "error",
                        });
                    });
            }
        });
    };

    const togglePasswordVisibility = (id) => {
        setShowPasswords((prev) => ({
            ...prev,
            [id]: !prev[id],
        }));
    };

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center m-3">
                <p style={{ fontWeight: "bolder" }}>{t("client.settings.card_tab")}</p>
                <button
                    type="button"
                    className="btn navyblue float-right"
                    disabled={isAddBtnDisabled}
                    onClick={handleCard}
                >
                    {t("work-contract.add_card")}
                </button>
            </div>
            {cards.length > 0 ? (
                cards.map((card, index) => (
                    <div
                        className="card"
                        key={index}
                        style={{
                            background: "#FAFBFC",
                            boxShadow: "none",
                            border: "1px solid #E5EBF1",
                            borderRadius: "10px",
                        }}
                    >
                        <div className="card-body">
                            <div className="d-flex justify-content-between align-items-center">
                                <p style={{ fontWeight: "bolder" }}>Card {index + 1}</p>
                                {card.is_default && <BsPinAngleFill />}
                                {!card.is_default && (
                                    <div className="action">
                                        <button
                                            onClick={() => handleMarkDefault(card.id)}
                                            style={{ fontSize: "20px", borderRadius: "5px" }}
                                        >
                                            <MdOutlineEdit />
                                        </button>
                                        <button
                                            onClick={() => handleDelete(card.id)}
                                            style={{ fontSize: "20px", borderRadius: "5px" }}
                                        >
                                            <RiDeleteBin6Line />
                                        </button>
                                    </div>
                                )}
                            </div>
                            <div className="form-group d-flex align-items-center mt-2 mb-2">
                                <label htmlFor="" style={{ width: "5.5rem" }}>
                                    {t("work-contract.card_number")}
                                </label>
                                <div className="input-wrapper" style={{ position: "relative", width: "100%" }}>
                                    <input
                                        type={showPasswords[card.id] ? "text" : "password"}
                                        value={card.card_number}
                                        className="custom-input"
                                        readOnly
                                        style={{ paddingRight: "2.5rem" }}
                                    />
                                    <span
                                        onClick={() => togglePasswordVisibility(card.id)}
                                        style={{
                                            position: "absolute",
                                            right: "0.75rem",
                                            top: "50%",
                                            transform: "translateY(-50%)",
                                            cursor: "pointer",
                                        }}
                                    >
                                        {showPasswords[card.id] ? <FaEyeSlash /> : <FaEye />}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                ))
            ) : (
                <p>{t("work-contract.no_cards_found")}</p>
            )}
        </div>
    );
}

import React, { useEffect, useState } from "react";
import { useAlert } from "react-alert";
import { useNavigate, useLocation } from "react-router-dom";
import Moment from "moment";
import { useTranslation } from "react-i18next";
import axios from "axios";
import { daDK } from "rsuite/esm/locales";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { Badge } from "react-bootstrap";
import Swal from "sweetalert2";
import { RiDeleteBin6Line } from "react-icons/ri";
import { MdOutlineEdit } from "react-icons/md";
import { FaEye, FaEyeSlash } from "react-icons/fa";
import { BsPinAngleFill } from "react-icons/bs";



export default function card() {
    const { t } = useTranslation();
    const [card, setCard] = useState([]);
    const [client, setClient] = useState([]);
    const [showPassword, setShowPassword] = useState(false);
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

        if (_paymentStatus == "payment-success") {
            swal(t("work-contract.messages.card_success"), "", "success");
            // swal(
            //     "Thanks, card is added successfully, Now you can sign contract!",
            //     "",
            //     "success"
            // );
            navigate(`/client/settings`);
        } else if (_paymentStatus == "payment-cancelled") {
            swal(t("work-contract.messages.card_adding_failed"), "", "error");
            navigate(`/client/settings`);
        }
    }, [queryParams]);

    const getCard = () => {
        axios.get(`/api/client/get-card`, { headers }).then((res) => {
            setCard(res.data.res);
        });
    };

    const getClient = () => {
        axios.get("/api/client/my-account", { headers }).then((response) => {
            setClient(response.data.account);
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
        getCard();
        getClient();
    }, []);

    const handleMarkDefault = (id) => {
        axios
            .put(`/api/client/cards/${id}/mark-default`, {}, { headers })
            .then((re) => {
                swal(t("work-contract.messages.card_updated"), "", "success");
                getCard();
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
                    .then((response) => {
                        Swal.fire(
                            t("global.deleted"),
                            t("work-contract.cardDeleted"),
                            "success"
                        );
                        setTimeout(() => {
                            getCard();
                        }, 1000);
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

    const togglePasswordVisibility = () => {
        setShowPassword(!showPassword);
    };

    // console.log(card);

    return (
        <div>
            <div className="d-flex justify-content-between align-items-center m-3">
                <p className=""
                    style={{ fontWeight: "bolder" }}
                >{t("client.settings.card_tab")}</p>
                <button
                    type="button"
                    className="btn navyblue float-right "
                    disabled={isAddBtnDisabled}
                    onClick={() => handleCard()}
                >
                    {t("work-contract.add_card")}
                </button>
            </div>
            {card?.length > 0 ? (
                card.map((card, index) => (
                    <div className="card" key={index}
                    style={{
                        background: "#FAFBFC",
                        boxShadow: "none",
                        border: "1px solid #E5EBF1",
                        borderRadius: "10px"
                    }}>
                        <div className="card-body">

                            {/* <div className="table-responsive">
                            <Table className="table table-bordered">
                                <Thead>
                                    <Tr>
                                        <Th>{t("work-contract.card_type")}</Th>
                                        <Th>{t("work-contract.card_number")}</Th>
                                        <Th scope="col">
                                            {t("work-contract.card_expiry")}
                                        </Th>
                                        <Th scope="col">{t("work-contract.action")}</Th>
                                    </Tr>
                                </Thead>
                                <Tbody>
                                    {card?.length > 0 ? (
                                        card.map((card, index) => {
                                            return (
                                                <Tr key={index}>
                                                    <Td>
                                                        {card.card_type}
                                                        {card.is_default && (
                                                            <Badge bg="success ml-3">
                                                                {t(
                                                                    "client.settings.default"
                                                                )}
                                                            </Badge>
                                                        )}
                                                    </Td>
                                                    <Td>{card.card_number}</Td>
                                                    <Td className="pl-3">
                                                        {card.valid}
                                                    </Td>
                                                    <Td
                                                        className="pl-3 "
                                                        style={{ width: "10%" }}
                                                    >
                                                        <div className="d-flex mx-2">
                                                            {!card.is_default && (
                                                                <>
                                                                    <button
                                                                        className="btn btn-sm btn-info mr-2"
                                                                        onClick={() =>
                                                                            handleMarkDefault(
                                                                                card.id
                                                                            )
                                                                        }
                                                                    >
                                                                        {t(
                                                                            "client.settings.markAsDefault"
                                                                        )}
                                                                    </button>
                                                                    <button
                                                                        className="btn btn-sm btn-danger ms-2"
                                                                        onClick={() =>
                                                                            handleDelete(
                                                                                card.id
                                                                            )
                                                                        }
                                                                    >
                                                                        <i className="fa fa-trash"></i>
                                                                    </button>
                                                                </>
                                                            )}
                                                        </div>
                                                    </Td>
                                                </Tr>
                                            );
                                        })
                                    ) : (
                                        <Tr>
                                            <Td colSpan="3">
                                                {t("work-contract.NoCardAdded")}
                                            </Td>
                                        </Tr>
                                    )}
                                </Tbody>
                            </Table>
                        </div> */}
                            <div className="item">
                                <div className="d-flex justify-content-between align-items-center">
                                    <div className="d-flex justify-content-between align-items-center w-100">
                                    <p className=""
                                        style={{ fontWeight: "bolder" }}
                                        >Card {index}</p>
                                        {card.is_default?(
                                            <span><BsPinAngleFill /></span>
                                            ): ""}
                                    </div>

                                     {!card.is_default && (
                                        <div className="action d-flex align-content-center justify-content-center">
                                        <button className="d-flex align-content-center justify-content-center p-1"
                                        onClick={() =>
                                            handleMarkDefault(
                                                card.id
                                            )
                                        }
                                            style={{ fontSize: "20px", borderRadius: "5px" }}
                                        ><MdOutlineEdit /></button>
                                        <button className="d-flex align-content-center justify-content-center ml-2 pt-1"
                                        onClick={() =>
                                            handleDelete(
                                                card.id
                                            )
                                        }
                                            style={{ fontSize: "20px", borderRadius: "5px" }}
                                        ><RiDeleteBin6Line /></button>
                                    </div>
                                     )}
                                    
                                </div>
                                <div className="">
                                    <div className="d-flex align-items-center mt-2 mb-2">
                                        <label htmlFor="" style={{ width: "5.5rem" }}>{t("work-contract.card_type")}</label>
                                        <input type="text" value={card.card_type} className="custom-input" style={{ width: "98%" }} />
                                    </div>
                                    <div className="form-group d-flex align-items-center mt-2 mb-2">
                                        <label htmlFor="" className="control-label" style={{ width: "5.5rem" }}>{t("work-contract.card_number")}</label>
                                        <div className="input-wrapper" style={{ position: "relative " }}>
                                            <input type={showPassword ? "text" : "password"} value={card.card_number} className="custom-input" style={{ paddingRight: "2.5rem", width: "98%" }} />
                                            <span
                                                onClick={() => togglePasswordVisibility("current")}
                                                style={{
                                                    position: "absolute",
                                                    right: "0.75rem",
                                                    top: "50%",
                                                    transform: "translateY(-50%)",
                                                    cursor: "pointer",
                                                    // width: "98%"

                                                }}
                                            >
                                                {showPassword ? <FaEyeSlash /> : <FaEye />}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="d-flex align-items-center mt-2 mb-2">
                                        <label htmlFor="" style={{ width: "5.5rem" }}>{t("work-contract.card_expiry")}</label>
                                        <input type="text" value={card.valid} className="custom-input" style={{ width: "98%" }} />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                ))

            ) : (
                <div className="card-item">
                    <div className="d-flex justify-content-between align-items-centem">
                        <p className="">Card Not Found</p>
                    </div>
                </div>
            )}

        </div>
    );
}

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

export default function card() {
    const { t } = useTranslation();
    const [card, setCard] = useState([]);
    const [client, setClient] = useState([]);
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
            // swal(t("work-contract.messages.card_success"), "", "success");
            swal(
                "Thanks, card is added successfully, Now you can sign contract!",
                "",
                "success"
            );
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
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Card!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/client/cards/${id}`, { headers })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "card has been deleted.",
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

    return (
        <div className="card">
            <div className="card-body">
                <button
                    type="button"
                    className="btn btn-pink float-right mb-3"
                    disabled={isAddBtnDisabled}
                    onClick={() => handleCard()}
                >
                    {t("work-contract.add_card")}
                </button>
                <div className="table-responsive">
                    <Table className="table table-bordered">
                        <Thead>
                            <Tr>
                                <Th>{t("work-contract.card_type")}</Th>
                                <Th>{t("work-contract.card_number")}</Th>
                                <Th scope="col">
                                    {t("work-contract.card_expiry")}
                                </Th>
                                <Th scope="col">Action</Th>
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
                                                        default
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
                                                                Mark as Default
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
                                    <Td colSpan="3">No card added</Td>
                                </Tr>
                            )}
                        </Tbody>
                    </Table>
                </div>
            </div>
        </div>
    );
}

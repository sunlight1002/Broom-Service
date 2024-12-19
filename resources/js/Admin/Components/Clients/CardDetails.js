import React, { useState, useEffect } from "react";
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { Badge } from "react-bootstrap";
import swal from "sweetalert";
import Swal from "sweetalert2";
import { useTranslation } from "react-i18next";

import AddCreditCardModal from "../Modals/AddCreditCardModal";

export default function CardDetails({ client }) {
    const { t } = useTranslation();
    const [cards, setCards] = useState([]);
    const [addCardModalOpen, setAddCardModalOpen] = useState(false);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getCards = async () => {
        if (client) {
            await axios
                .get(`/api/admin/client/${client.id}/cards`, { headers })
                .then((res) => {
                    setCards(res.data.cards);
                });
        }
    };

    const handleCard = () => {
        setAddCardModalOpen(true);
    };

    const handleMarkDefault = async (id) => {
        await axios
            .put(
                `/api/admin/client/${client.id}/cards/${id}/mark-default`,
                {},
                { headers }
            )
            .then((re) => {
                swal("Card has been updated", "", "success");
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

    const handleDelete = async (id) => {
        Swal.fire({
            title: t("common.delete.title"),
            text: t("common.delete.message"),
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            cancelButtonText: t("common.delete.cancel"),
            confirmButtonText: t("common.delete.confirm_card"),
        }).then(async (result) => {
            if (result.isConfirmed) {
                await axios
                    .delete(`/api/admin/client/${client.id}/cards/${id}`, {
                        headers,
                    })
                    .then((response) => {
                        Swal.fire(
                            t("common.delete.deleted"),
                            t("common.delete.card_deleted"),
                            t("common.delete.success")
                        );
                        setTimeout(() => {
                            getCards();
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

    useEffect(() => {
        getCards();
    }, [client]);

    return (
        <>
            <button
                type="button"
                className="btn btn-pink float-right mb-3"
                disabled={addCardModalOpen}
                onClick={() => handleCard()}
            >
                {t("global.addCard")}
            </button>
            <div className="table-responsive">
                <Table className="table table-bordered">
                    <Thead>
                        <Tr>
                            <Th>Card Type</Th>
                            <Th>Card Number</Th>
                            <Th scope="col">Expiry</Th>
                            <Th scope="col">Action</Th>
                        </Tr>
                    </Thead>
                    <Tbody>
                        {cards?.length > 0 ? (
                            cards.map((card, index) => {
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
                                        <Td className="pl-3">{card.valid}</Td>
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
                                <Td colSpan="3">{t("global.noCard")}</Td>
                            </Tr>
                        )}
                    </Tbody>
                </Table>
            </div>

            {addCardModalOpen && (
                <AddCreditCardModal
                    isOpen={addCardModalOpen}
                    setIsOpen={setAddCardModalOpen}
                    onSuccess={() => getCards()}
                    clientId={client.id}
                />
            )}
        </>
    );
}

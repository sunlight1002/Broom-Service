import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";

export default function AddCreditCardModal({
    setIsOpen,
    isOpen,
    clientId,
    onSuccess,
}) {
    const alert = useAlert();

    const [isLoading, setIsLoading] = useState(false);
    const [sessionURL, setSessionURL] = useState("");
    const [clientCardSessionID, setClientCardSessionID] = useState(null);
    const [checkingClientIDForCard, setCheckingClientIDForCard] =
        useState(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleAddingClientCard = (_clientID) => {
        if (checkingClientIDForCard) {
            alert.info("Adding card is already in-progress");
            return false;
        }

        alert.info("Adding card in progress");

        axios
            .post(
                `/api/admin/client/${_clientID}/initialize-card`,
                {},
                { headers }
            )
            .then((response) => {
                setCheckingClientIDForCard(_clientID);

                setClientCardSessionID(response.data.session_id);
                setSessionURL(response.data.redirect_url);
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
        let _intervalID;

        if (checkingClientIDForCard && clientCardSessionID) {
            _intervalID = setInterval(() => {
                if (checkingClientIDForCard) {
                    axios
                        .post(
                            `/api/admin/client/${checkingClientIDForCard}/check-card-by-session`,
                            { session_id: clientCardSessionID },
                            { headers }
                        )
                        .then((response) => {
                            if (response.data.status == "completed") {
                                alert.success("Card added successfully");
                                setCheckingClientIDForCard(null);
                                setClientCardSessionID(null);
                                clearInterval(_intervalID);
                                onSuccess();
                            }
                        })
                        .catch((e) => {
                            setCheckingClientIDForCard(null);
                            setClientCardSessionID(null);
                            clearInterval(_intervalID);

                            Swal.fire({
                                title: "Error!",
                                text: e.response.data.message,
                                icon: "error",
                            });
                        });
                }
            }, 2000);
        }

        return () => clearInterval(_intervalID);
    }, [checkingClientIDForCard, clientCardSessionID]);

    useEffect(() => {
        if (isOpen && clientId) {
            handleAddingClientCard(clientId);
        }
    }, [clientId, isOpen]);

    return (
        <Modal
            size="lg"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
            backdrop="static"
        >
            <Modal.Header closeButton>
                <Modal.Title>Add New Credit Card</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        {sessionURL ? (
                            <div className="form-group">
                                <iframe
                                    src={sessionURL}
                                    title="Pay Card Transaction"
                                    width="100%"
                                    height="800"
                                ></iframe>
                            </div>
                        ) : (
                            <Skeleton height={250} />
                        )}
                    </div>
                </div>
            </Modal.Body>

            <Modal.Footer>
                <Button
                    type="button"
                    className="btn btn-secondary"
                    disabled={isLoading}
                    onClick={() => {
                        setIsOpen(false);
                    }}
                >
                    Close
                </Button>
            </Modal.Footer>
        </Modal>
    );
}

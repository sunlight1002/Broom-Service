import { useEffect, useState, useMemo, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import moment from "moment";
import Flatpickr from "react-flatpickr";
import "flatpickr/dist/flatpickr.css";
import Comments from "../common/Comments";

export default function CommentsModal({
    setIsOpen,
    isOpen,
    relationID,
    routeType,
    canAddComment,
    size = "md",
}) {
    return (
        <Modal
            size={size}
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
            backdrop="static"
        >
            <Modal.Header closeButton>
                <Modal.Title>Comments</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <Comments
                    relationID={relationID}
                    routeType={routeType}
                    canAddComment={canAddComment}
                />
            </Modal.Body>

            <Modal.Footer>
                <Button
                    type="button"
                    className="btn btn-secondary"
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

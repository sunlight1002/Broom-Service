import { useEffect, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useParams } from "react-router-dom";

const ContractFileModal = ({
    isOpen,
    setIsOpen,
    contractId,
    handleContractSubmit,
}) => {
    const param = useParams();
    let docFile = useRef(null);

    const handleSave = (e) => {
        e.preventDefault();
        const data = new FormData();
        data.append("contractId", contractId);
        if (docFile.current && docFile.current.files.length > 0) {
            data.append("file", docFile.current.files[0]);
        }
        handleContractSubmit(data);
    };
    const resetForm = () => {
        if (docFile.current) {
            docFile.current.value = "";
            docFile.current.type = "text";
            docFile.current.type = "file";
        }
    };
    useEffect(() => {
        if (isOpen) {
            resetForm();
        }
    }, [isOpen]);

    return (
        <Modal
            size="xl"
            className="modal-container"
            show={isOpen}
            onHide={() => {
                setIsOpen(false);
            }}
        >
            <Modal.Header closeButton>
                <Modal.Title>Upload Contract File</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label
                                htmlFor="contractFile"
                                className="form-label"
                            >
                                Upload file
                            </label>
                            <input
                                ref={docFile}
                                className="form-control"
                                type="file"
                                accept="application/pdf"
                                id="contractFile"
                            />
                        </div>
                    </div>
                </div>
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
                <Button
                    type="button"
                    onClick={(e) => handleSave(e)}
                    className="btn btn-primary"
                >
                    Save
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default ContractFileModal;

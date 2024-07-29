import { useEffect, useRef } from "react";
import { Button, Modal } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import { useParams } from "react-router-dom";

const DocumentModal = ({ isOpen, setIsOpen, handleDocSubmit, docTypes }) => {
    const { t } = useTranslation();
    const param = useParams();
    let docTypeRef = useRef(null);
    let docFile = useRef(null);

    const handleDocData = (e) => {
        e.preventDefault();
        const data = new FormData();
        data.append("id", param.id);
        data.append("doc_id", docTypeRef.current.value);
        if (docFile.current && docFile.current.files.length > 0) {
            data.append("file", docFile.current.files[0]);
        }
        handleDocSubmit(data);
    };
    const resetForm = () => {
        docTypeRef.current && (docTypeRef.current.value = "");
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
                <Modal.Title>{t("global.addDocument")}</Modal.Title>
            </Modal.Header>

            <Modal.Body>
                <div className="row">
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                            {t("worker.settings.document_type")}
                            </label>
                            <select className="form-control" ref={docTypeRef}>
                                <option value={""}>{t("global.select_default_option")}</option>
                                {docTypes.map((d) => (
                                    <option value={d.id} key={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label htmlFor="cmtFiles" className="form-label">
                                {t("client.jobs.view.file")}
                            </label>
                            <input
                                ref={docFile}
                                className="form-control"
                                type="file"
                                accept="application/pdf"
                                id="cmtFiles"
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
                    {t("modal.close")}
                </Button>
                <Button
                    type="button"
                    onClick={(e) => handleDocData(e)}
                    className="btn btn-primary"
                >
                    {t("modal.save")}
                </Button>
            </Modal.Footer>
        </Modal>
    );
};

export default DocumentModal;

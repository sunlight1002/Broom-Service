import { useEffect, useRef, useState } from "react";
import { Button, Modal } from "react-bootstrap";
import { useTranslation } from "react-i18next";
import { useParams } from "react-router-dom";
import moment from "moment";

const DocumentModal = ({ isOpen, setIsOpen, handleDocSubmit, docTypes }) => {
    const { t } = useTranslation();
    const param = useParams();
    const docTypeRef = useRef(null);
    const docFile = useRef(null);

    const [selectedDocType, setSelectedDocType] = useState("");
    const [selectedDocTypeName, setSelectedDocTypeName] = useState("");
    const [otherDocName, setOtherDocName] = useState("");
    const [date, setDate] = useState(moment().format("YYYY-MM-DD"));

    const handleDocData = (e) => {
        e.preventDefault();
        const data = new FormData();
        data.append("id", param.id);
        data.append("doc_id", selectedDocType);
        if(date){
            data.append("date", date);
        }
        if (docFile.current && docFile.current.files.length > 0) {
            data.append("file", docFile.current.files[0]);
        }
        if (selectedDocType === "7") {
            data.append("other_doc_name", otherDocName);
        }
        handleDocSubmit(data);
    };

    const resetForm = () => {
        setSelectedDocType("");
        setOtherDocName("");
        if (docTypeRef.current) {
            docTypeRef.current.value = "";
        }
        if (docFile.current) {
            docFile.current.value = "";
            docFile.current.type = "text"; // Reset input type to clear the file
            docFile.current.type = "file"; // Reset back to file type
        }
    };

    useEffect(() => {
        if (isOpen) {
            resetForm();
        }
    }, [isOpen]);

    const handleSelectedDocType = (e) => {
        const target = e.target;

        const selectedOption = target.options[target.selectedIndex];
        setSelectedDocTypeName(selectedOption.getAttribute("name"));


        setSelectedDocType(target.value);
        if (e.target.value === "7") {
            setOtherDocName("");
        }
    };



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
                    {/* Document Type */}
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label className="control-label">
                                {t("worker.settings.document_type")}
                            </label>
                            <select
                                className="form-control"
                                ref={docTypeRef}
                                value={selectedDocType}
                                onChange={(e) => handleSelectedDocType(e)}
                            >
                                <option value="">{t("global.select_default_option")}</option>
                                {docTypes.map((d) => (
                                    <option value={d.id} name={d.slug} key={d.id}>
                                        {d.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {selectedDocTypeName == "payslip" && (
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.date")}</label>
                                <div className="d-flex align-items-center flex-wrap">
                                    <input
                                        className="form-control my-1"
                                        type="date"
                                        placeholder="Enter Date"
                                        name="date"
                                        value={date}
                                        onChange={(e) => setDate(e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Other Document Name (Conditional) */}
                    {selectedDocTypeName == "others" && (
                        <div className="col-sm-12">
                            <div className="form-group">
                                <label className="control-label">{t("global.document_name")}</label>
                                <input
                                    type="text"
                                    value={otherDocName}
                                    onChange={(e) => setOtherDocName(e.target.value)}
                                    className="form-control"
                                    required
                                    placeholder={t("global.enter_document_name")}
                                />
                            </div>
                        </div>
                    )}

                    {/* File Upload */}
                    <div className="col-sm-12">
                        <div className="form-group">
                            <label htmlFor="cmtFiles" className="form-label">
                                {t("client.jobs.view.file")}
                            </label>
                            <input
                                ref={docFile}
                                className="form-control"
                                type="file"
                                accept="application/pdf, image/*"
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

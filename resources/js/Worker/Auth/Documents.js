import axios from "axios";
import React, { useState, useEffect, useMemo } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import DocumentList from "../../Admin/Components/Documents/DocumentList";

export default function Documents() {
    const [file, setFile] = useState(false);
    const [documents, setDocuments] = useState([]);
    const [pdf, setPdf] = useState(null);
    const [worker, setWorker] = useState({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [alldocumentTypes, setAllDocumentTypes] = useState([]);
    const [formValues, setFormValues] = useState({
        type: "",
        name: "",
    });

    const alert = useAlert();
    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("worker-token"),
    };

    const handlePdfUpload = (e) => {
        setPdf(e.target.files[0]);
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();

        if (!pdf) {
            alert.error("The file is missing");
            return false;
        }

        if (!formValues.type) {
            alert.error("The type is missing");
            return false;
        }

        if (!formValues.name) {
            alert.error("The name is missing");
            return false;
        }
        setIsSubmitting(true);

        const formData = new FormData();
        formData.append("type", formValues.type);
        formData.append("name", formValues.name);
        formData.append("file", pdf);

        axios
            .post(`/api/upload`, formData, { headers })
            .then((response) => {
                document.querySelector(".closedoc").click();
                alert.success(t("worker.settings.formUplodSuccess"));
                getDocuments();
                setIsSubmitting(false);
            })
            .catch((error) => {
                console.log(error);
                setIsSubmitting(false);
            });
    };

    const getWorker = () => {
        const headers = {
            Accept: "application/json, text/plain, */*",
            "Content-Type": "application/json",
            Authorization: `Bearer ` + localStorage.getItem("worker-token"),
        };
        axios.get(`/api/details`, { headers }).then((response) => {
            setWorker(response.data.success);
            setFile(response.data.success.form_101);
        });
    };

    const getDocuments = () => {
        axios.get(`/api/documents`, { headers }).then((res) => {
            if (res.data && res.data) {
                setDocuments(res.data.documents);
            }
        });
    };

    const getDocumentTypes = () => {
        axios.get(`/api/doc-types`, { headers }).then((res) => {
            if (res.data && res.data.documentTypes.length > 0) {
                setAllDocumentTypes(res.data.documentTypes);
            }
        });
    };

    const documentTypes = useMemo(() => {
        return alldocumentTypes.filter((i) => i.slug !== "payslip");
    }, [worker, alldocumentTypes]);

    useEffect(() => {
        getWorker();
        getDocumentTypes();
        getDocuments();
    }, []);

    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            <div className="row pb-3 py-3 m-3">
                <div className="col-sm-10">
                    {file && (
                        <a
                            href={`/api/showPdf/${localStorage.getItem(
                                "worker-id"
                            )}`}
                            target="_blank"
                            className="btn btn-pink"
                        >
                            {t("worker.settings.view_form")}
                        </a>
                    )}
                </div>
                <div className="col-sm-2">
                    <button
                        type="button"
                        className="btn btn-pink mt-3 mt-md-0"
                        data-toggle="modal"
                        data-target="#exampleModal"
                    >
                        {t("worker.settings.add_file")}
                    </button>
                </div>
            </div>
            <div className="col-md-12">
                <DocumentList documents={documents} worker={worker} />
            </div>
            <div
                className="modal fade"
                id="exampleModal"
                tabIndex="-1"
                role="dialog"
                aria-labelledby="exampleModalLabel"
                aria-hidden="true"
            >
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <h5 className="modal-title" id="exampleModalLabel">
                                {t("worker.settings.add_file")}
                            </h5>
                            <button
                                type="button"
                                className="close"
                                data-dismiss="modal"
                                aria-label="Close"
                            >
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            {t("worker.settings.document_type")}
                                        </label>
                                        <select
                                            className="form-control"
                                            value={formValues.type}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    type: e.target.value,
                                                });
                                            }}
                                        >
                                            <option value={""}>
                                                {t(
                                                    "global.select_default_option"
                                                )}
                                            </option>
                                            {documentTypes.map((d) => (
                                                <option value={d.id} key={d.id}>
                                                    {d.name}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="form-label">
                                            {t("worker.settings.document_name")}
                                        </label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={formValues.name}
                                            onChange={(e) => {
                                                setFormValues({
                                                    ...formValues,
                                                    name: e.target.value,
                                                });
                                            }}
                                        />
                                    </div>
                                </div>
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="form-label">
                                            {t("worker.settings.file")}
                                        </label>
                                        <input
                                            type="file"
                                            accept="application/pdf"
                                            className="form-control"
                                            onChange={handlePdfUpload}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button
                                type="button"
                                className="btn btn-secondary closedoc"
                                data-dismiss="modal"
                            >
                                {t("worker.settings.close")}
                            </button>
                            <button
                                type="button"
                                onClick={handleFormSubmit}
                                className="btn btn-primary"
                                disabled={isSubmitting}
                            >
                                {t("worker.settings.save_file")}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

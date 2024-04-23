import axios from "axios";
import React, { useState, useEffect, useRef, useMemo } from "react";
import { useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import DocumentList from "../Documents/DocumentList";
import DocumentModal from "../Documents/DocumentModal";

export default function Document({ worker }) {
    const [alldocumentTypes, setAllDocumentTypes] = useState([]);
    const [documents, setDocuments] = useState({});
    const [isOpenDocumentModal, setIsOpenDocumentModal] = useState(false);

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleDelete = (e, id) => {
        e.preventDefault();
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, Delete Document",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/document/remove/${id}/${worker.id}`, {
                        headers,
                    })
                    .then((response) => {
                        Swal.fire(
                            "Deleted!",
                            "Document has been deleted.",
                            "success"
                        );
                        setTimeout(() => {
                            getDocuments();
                        }, 1000);
                    });
            }
        });
    };

    const getDocuments = () => {
        axios
            .get(`/api/admin/documents/${parseInt(worker.id)}`, { headers })
            .then((response) => {
                setDocuments(response.data.documents);
            });
    };

    const getDocumentTypes = () => {
        axios.get(`/api/admin/get-doc-types`, { headers }).then((res) => {
            if (res.data && res.data.documentTypes.length > 0) {
                setAllDocumentTypes(res.data.documentTypes);
            }
        });
    };

    useEffect(() => {
        getDocuments();
        getDocumentTypes();
    }, []);

    const handleAddDocument = () => {
        if (
            worker.country !== "Israel" &&
            (!worker.visa ||
                !worker.passport ||
                worker.visa === "" ||
                worker.passport === "")
        ) {
            alert.error("Please add required document : visa & passport");
            return;
        }

        setIsOpenDocumentModal(true);
    };

    const handleDocSubmit = (data) => {
        save(data);
    };

    const save = (data) => {
        axios
            .post(`/api/admin/document/save`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e][0]);
                    }
                } else {
                    alert.success(res.data.message);
                    getDocuments();
                    setIsOpenDocumentModal(false);
                }
            })
            .catch((err) => {
                alert.error("Error!");
            });
    };

    const handleFileChange = (e, type) => {
        const data = new FormData();
        data.append("id", worker.id);
        if (e.target.files.length > 0) {
            data.append(`${type}`, e.target.files[0]);
        }
        save(data);
    };

    const btnSelect = (type) => {
        document.getElementById(`${type}`).click();
    };

    const documentTypes = useMemo(() => {
        if (worker.company_type === "my-company") {
            if (worker.country === "Israel") {
                return alldocumentTypes.filter((i) => i.slug !== "israeli-id");
            } else {
                return alldocumentTypes.filter((i) => i.slug === "payslip");
            }
        } else {
            if (worker.country === "Israel") {
                return alldocumentTypes.filter(
                    (i) => !["payslip", "israeli-id"].includes(i.slug)
                );
            } else {
            }
        }

        return alldocumentTypes;
    }, [worker, alldocumentTypes]);

    return (
        <div
            className="tab-pane fade active show"
            id="customer-documents"
            role="tabpanel"
            aria-labelledby="customer-documents-tab"
        >
            <div className="text-right pb-3">
                {worker.country !== "Israel" && (
                    <>
                        {worker.visa === null && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => btnSelect("visaSelect")}
                                    className="btn btn-success m-3"
                                >
                                    Upload Visa
                                </button>
                                <input
                                    className="form-control d-none"
                                    id="visaSelect"
                                    type="file"
                                    accept="application/pdf"
                                    onChange={(e) =>
                                        handleFileChange(e, "visa")
                                    }
                                ></input>
                            </>
                        )}
                        {worker.passport === null && (
                            <>
                                <button
                                    type="button"
                                    onClick={() => btnSelect("passportSelect")}
                                    className="btn btn-success m-3"
                                >
                                    Upload Passport
                                </button>
                                <input
                                    className="form-control d-none"
                                    id="passportSelect"
                                    type="file"
                                    accept="application/pdf"
                                    onChange={(e) =>
                                        handleFileChange(e, "passport")
                                    }
                                ></input>
                            </>
                        )}
                    </>
                )}
                <button
                    type="button"
                    onClick={() => handleAddDocument()}
                    className="btn btn-success m-3"
                >
                    Add Document
                </button>
            </div>
            <DocumentList
                documents={documents}
                worker={worker}
                handleDelete={handleDelete}
            />
            {isOpenDocumentModal && (
                <DocumentModal
                    isOpen={isOpenDocumentModal}
                    setIsOpen={setIsOpenDocumentModal}
                    handleDocSubmit={handleDocSubmit}
                    docTypes={documentTypes}
                />
            )}
        </div>
    );
}

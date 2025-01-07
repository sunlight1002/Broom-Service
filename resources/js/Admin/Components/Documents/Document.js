import axios from "axios";
import React, { useState, useEffect, useRef, useMemo } from "react";
import { useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import DocumentList from "../Documents/DocumentList";
import DocumentModal from "../Documents/DocumentModal";
import { useTranslation } from "react-i18next";

export default function Document({ worker, getWorkerDetails }) {

    const { t } = useTranslation();
    const [alldocumentTypes, setAllDocumentTypes] = useState([]);
    const [documents, setDocuments] = useState([]);
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
                        getDocuments();
                        getWorkerDetails()
                    });
            }
        });
    };

    const getDocuments = () => {
        axios
            .get(`/api/admin/documents/${parseInt(worker.id)}`, { headers })
            .then((response) => {
                console.log(response.data.documents);

                setDocuments(response.data.documents);
            });
    };

    const getDocumentTypes = () => {
        axios.get(`/api/admin/get-doc-types`, { headers }).then((res) => {
            if (res.data && res.data.documentTypes.length > 0) {
                console.log(res.data.documentTypes);

                setAllDocumentTypes(res.data.documentTypes);
            }
        });
    };



    const handleAddDocument = () => {
        // if (
        //     worker.country !== "Israel" &&
        //     (!worker.visa ||
        //         !worker.passport ||
        //         worker.visa === "" ||
        //         worker.passport === "")
        // ) {
        //     alert.error("Please add required document : visa & passport");
        //     return;
        // }

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
                    getWorkerDetails()
                    setIsOpenDocumentModal(false);
                }
            })
            .catch((err) => {
                alert.error("all fields are required!");
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
                return alldocumentTypes.filter((i) =>
                    // ["israeli-id", "pension-form", "study-form" , "training-fund-form", "payslip", "others"].includes(i.slug)
                    ["israeli-id", "pension-form", "study-form", "payslip", "others"].includes(i.slug)

                );
            } else {
                return alldocumentTypes.filter((i) =>
                    // ["pension-form", "training-fund-form","study-form", "payslip" , "insurance-form", "others"].includes(i.slug)
                    ["payslip", "insurance-form", "others"].includes(i.slug)

                );
            }
        }else{
            return alldocumentTypes.filter((i) =>
                ["payslip", "insurance-form", "others"].includes(i.slug)
            );
        }
        // If not "my-company", return all documents
        return alldocumentTypes;
    }, [worker, alldocumentTypes]);

    useEffect(() => {
        getDocuments();
        getDocumentTypes();
    }, [worker]);

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
                                    {t("global.uploadVisa")}
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
                                    {t("global.uploadPassport")}
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

                {
                    worker?.country === "Israel" && (
                        <>
                            <button
                                type="button"
                                onClick={() => btnSelect("idCardSelect")}
                                className="btn btn-success m-3"
                            >
                                Id card
                            </button>
                            <input
                                className="form-control d-none"
                                id="idCardSelect"
                                type="file"
                                accept="application/pdf"
                                onChange={(e) =>
                                    handleFileChange(e, "id_card")
                                }
                            ></input>
                        </>
                    )
                }
                {/* {
                    worker && worker?.company_type !== "manpower" && (
                        <button
                            type="button"
                            onClick={() => handleAddDocument()}
                            className="btn btn-success m-3"
                        >
                            {t("global.addDocument")}
                        </button>
                    )
                } */}

                <button
                    type="button"
                    onClick={() => handleAddDocument()}
                    className="btn btn-success m-3"
                >
                    {t("global.addDocument")}
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

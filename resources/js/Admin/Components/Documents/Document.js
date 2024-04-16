import axios from "axios";
import React, { useState, useEffect, useRef } from "react";
import { useParams } from "react-router-dom";
import { useAlert } from "react-alert";
import Swal from "sweetalert2";
import DocumentList from "../Documents/DocumentList";
import DocumentModal from "../Documents/DocumentModal";

export default function Document() {
    const param = useParams();
    const alert = useAlert();
    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "multipart/form-data",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };
    const [isDocToggle, setIsDocToggle] = useState(false);
    const [alldocumentTypes, setAllDocumentTypes] = useState([]);
    const [documentTypes, setDocumentTypes] = useState([]);
    const [user, setUser] = useState({});

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
                    .delete(`/api/admin/document/remove/${id}/${param.id}`, {
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
            .get(`/api/admin/documents/${parseInt(param.id)}`, { headers })
            .then((res) => {
                if (res.data && res.data.user) {
                    setUser(res.data.user);
                }
            });
    };

    const getDocumentTypes = () => {
        axios.get(`/api/admin/get-doc-types`, { headers }).then((res) => {
            if (res.data && res.data.documentTypes.length > 0) {
                setAllDocumentTypes(res.data.documentTypes);
                setDocumentTypes(res.data.documentTypes);
            }
        });
    };

    useEffect(() => {
        getDocuments();
        getDocumentTypes();
    }, []);

    const handleDocToggle = () => {
        if (!isDocToggle) {
            if (
                user.country !== "Israel" &&
                (!user.visa ||
                    !user.passport ||
                    user.visa === "" ||
                    user.passport === "")
            ) {
                alert.error("Please add required document : visa & passport");
                return;
            }

            if (user.country !== "Israel") {
                setDocumentTypes(
                    alldocumentTypes.filter(
                        (i) =>
                            !["pension-form", "training-fund-form"].includes(
                                i.slug
                            )
                    )
                );
            } else {
                setDocumentTypes(alldocumentTypes);
            }
        }
        setIsDocToggle((prev) => !prev);
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
                    if (isDocToggle) {
                        handleDocToggle();
                    }
                    alert.success(res.data.message);
                    getDocuments();
                }
            })
            .catch((err) => {
                alert.error("Error!");
            });
    };
    const handleFileChange = (e, type) => {
        const data = new FormData();
        data.append("id", param.id);
        if (e.target.files.length > 0) {
            data.append(`${type}`, e.target.files[0]);
        }
        save(data);
    };
    const btnSelect = (type) => {
        document.getElementById(`${type}`).click();
    };
    return (
        <div
            className="tab-pane fade active show"
            id="customer-notes"
            role="tabpanel"
            aria-labelledby="customer-notes-tab"
        >
            <div className="text-right pb-3">
                {user.country !== "Israel" && (
                    <>
                        {user.visa === null && (
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
                        {user.passport === null && (
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
                    onClick={() => handleDocToggle()}
                    className="btn btn-success m-3"
                >
                    Add Document
                </button>
            </div>
            <DocumentList
                documents={user.documents}
                user={user}
                handleDelete={handleDelete}
            />
            {isDocToggle && (
                <DocumentModal
                    isDocToggle={isDocToggle}
                    handleDocToggle={handleDocToggle}
                    handleDocSubmit={handleDocSubmit}
                    docTypes={documentTypes}
                />
            )}
        </div>
    );
}

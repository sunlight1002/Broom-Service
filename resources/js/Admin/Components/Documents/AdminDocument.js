import React, { useState, useEffect, useMemo } from 'react'
import DocumentModal from './DocumentModal';
import { useTranslation } from 'react-i18next';
import { useAlert } from 'react-alert';
import Swal from 'sweetalert2';
import axios from 'axios';
import AdminDocsList from './AdminDocsList';

const AdminDocument = () => {
    const { t } = useTranslation();
    const [alldocumentTypes, setAllDocumentTypes] = useState([]);
    const [documents, setDocuments] = useState([]);
    const [isOpenDocumentModal, setIsOpenDocumentModal] = useState(false);

    const alert = useAlert();

    const adminId = localStorage.getItem("admin-id");

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
                    .delete(`/api/admin/document/remove-admin/${id}/${adminId}`, {
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
            .get(`/api/admin/document/admin/${parseInt(adminId)}`, { headers })
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
        setIsOpenDocumentModal(true);
    };

    const handleDocSubmit = (data) => {
        save(data);
    };

    const save = (data) => {
        axios
            .post(`/api/admin/document/admin-save`, data, { headers })
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
                console.log(err);
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
        return alldocumentTypes.filter((i) =>
            ["payslip", "others"].includes(i.slug)
        );
    }, [alldocumentTypes]);

    useEffect(() => {
        getDocuments();
        getDocumentTypes();
    }, []);

    return (
        <div
            className="tab-pane fade active show"
            id="customer-documents"
            role="tabpanel"
            aria-labelledby="customer-documents-tab"
        >
            <div className="text-right pb-3">
                <button
                    type="button"
                    onClick={() => handleAddDocument()}
                    className="btn navyblue m-3"
                >
                    {t("global.addDocument")}
                </button>

            </div>
            <AdminDocsList
                documents={documents}
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
    )
}

export default AdminDocument
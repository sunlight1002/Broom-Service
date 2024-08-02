import React, { useState, useEffect } from 'react';
import { Table, Thead, Tbody, Tr, Th, Td } from "react-super-responsive-table";
import { useTranslation } from 'react-i18next';
import { Button, Modal } from "react-bootstrap";
import { useAlert } from 'react-alert';
import axios from 'axios';

export const SubService = ({ params }) => {
    const { t } = useTranslation();
    const [show, setShow] = useState(false);
    const [subData, setSubData] = useState([]);
    const alert = useAlert();
    const [subService, setSubService] = useState({
        name_en: '',
        name_heb: '',
        apartment_size: '',
        price: '',
    });
    const [editMode, setEditMode] = useState(false);
    const [editId, setEditId] = useState(null);

    useEffect(() => {
        handleGetSubServices();
    }, []);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setSubService(prevState => ({
            ...prevState,
            [name]: value,
        }));
    };

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleGetSubServices = async () => {
        try {
            const res = await axios.get(`/api/admin/get-sub-services/${params.id}`, { headers });
            setSubData(res.data.subServices || []);
        } catch (error) {
            console.log("Error fetching sub-services:", error);
        }
    };

    const handleSubServices = async () => {
        if (editMode) {
            await handleUpdateSubService(editId);
        } else {
            await handleAddSubService();
        }
    };

    const handleAddSubService = async () => {
        try {
            const res = await axios.post(`/api/admin/add-sub-service/${params.id}`, subService, { headers });
            alert.success(res?.data?.message);
            setSubData(prevData => [...prevData, res.data.subService]);
            resetForm();
        } catch (error) {
            if (error.response && error.response.data.errors) {
                // Display each validation error
                Object.values(error.response.data.errors).forEach((messages) => {
                    messages.forEach(message => alert.error(message));
                });
            } else {
                console.log("Error adding sub-service:", error);
                alert.error("An unexpected error occurred.");
            }
        }
    };
    

    const handleUpdateSubService = async (id) => {
        try {
            const res = await axios.put(`/api/admin/edit-sub-service/${id}`, subService, { headers });
            alert.success(res?.data?.message);
            setSubData(prevData => prevData.map(item => item.id === id ? res?.data?.subService || [] : item));
            resetForm();
        } catch (error) {
            if (error.response && error.response.data.errors) {
                // Display each validation error
                Object.values(error.response.data.errors).forEach((messages) => {
                    messages.forEach(message => alert.error(message));
                });
            } else {
                console.log("Error adding sub-service:", error);
                alert.error("An unexpected error occurred.");
            }
        }
    };

    const handleDelete = async (id) => {
        try {
            const res = await Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Yes, Delete Sub-Service!",
            });

            if (res.isConfirmed) {
                const response = await axios.delete(`/api/admin/remove-sub-service/${id}`, { headers });
                alert.success(response?.data?.message);
                
                // Update state immediately to reflect the deletion
                setSubData(prevData => prevData.filter(item => item.id !== id));

                Swal.fire(
                    "Deleted!",
                    "Sub-service has been deleted.",
                    "success"
                );
            }
        } catch (error) {
            console.log("Error deleting sub-service:", error);
        }
    };
    

    const handleEdit = (sub) => {
        setEditMode(true);
        setEditId(sub.id);
        // console.log(sub);
        setSubService({
            name_en: sub.name_en || '',
            name_heb: sub.name_heb || '',
            apartment_size: sub.apartment_size || '',
            price: sub.price || '',
        });
        setShow(true);
    };

    const resetForm = () => {
        setSubService({
            name_en: '',
            name_heb: '',
            apartment_size: '',
            price: '',
        });
        setEditMode(false);
        setEditId(null);
        setShow(false);
    };

    return (
        <div>
            <div className="action-dropdown dropdown text-right mb-3">
                <button
                    className="btn btn-primary mr-3"
                    onClick={() => {
                        resetForm();
                        setShow(true);
                    }}
                >
                    Add Sub-service
                </button>
            </div>
            <div className="table-responsive">
                <Table className="table table-bordered">
                    <Thead>
                        <Tr>
                            <Th>Services-En</Th>
                            <Th>Services-Heb</Th>
                            <Th>{t("Apartment Size")}</Th>
                            <Th>{t("Price")}</Th>
                            <Th>Actions</Th>
                        </Tr>
                    </Thead>
                    <Tbody>
                        {subData.length > 0 ? (
                            subData.map((sub, i) => (
                                <Tr key={i}>
                                    <Td>{sub.name_en}</Td>
                                    <Td>{sub.name_heb}</Td>
                                    <Td>{sub.apartment_size}</Td>
                                    <Td>{sub.price}</Td>
                                    <Td>
                                        <div className="action-dropdown d-flex justify-content-center dropdown">
                                            <button
                                                className="btn btn-default dropdown-toggle"
                                                type="button"
                                                id="dropdownMenuButton"
                                                data-toggle="dropdown"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                            >
                                                <i className="fa fa-ellipsis-vertical"></i>
                                            </button>
                                            <div className="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                <button type="button" onClick={() => handleEdit(sub)} className="dropdown-item">
                                                    Edit
                                                </button>
                                                <button type="button" onClick={() => handleDelete(sub.id)} className="dropdown-item">
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </Td>
                                </Tr>
                            ))
                        ) : (
                            <Tr>
                                <Td colSpan="5" className="text-center">No data available</Td>
                            </Tr>
                        )}
                    </Tbody>
                </Table>
            </div>

            {show && (
                <Modal
                    size="md"
                    className="modal-container"
                    show={show}
                    backdrop="static"
                >
                    <Modal.Header closeButton>
                        <Modal.Title>{editMode ? 'Edit Sub-service' : 'Add Sub-service'}</Modal.Title>
                    </Modal.Header>

                    <Modal.Body>
                        <div className="row">
                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">Name-en</label>
                                    <input
                                        className="form-control"
                                        name='name_en'
                                        type="text"
                                        id="Name-en"
                                        value={subService.name_en || ''}
                                        onChange={handleChange}
                                        placeholder='Service name in English'
                                    />
                                </div>
                            </div>

                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">Name-heb</label>
                                    <input
                                        className="form-control"
                                        name='name_heb'
                                        type="text"
                                        id="Name-heb"
                                        value={subService.name_heb || ''}
                                        onChange={handleChange}
                                        placeholder='שם השירות באנגלית'
                                    />
                                </div>
                            </div>

                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">Apartment Size</label>
                                    <input
                                        className="form-control"
                                        name='apartment_size'
                                        type="text"
                                        id="Apartment"
                                        value={subService.apartment_size || ''}
                                        onChange={handleChange}
                                        placeholder='Size of the apartment'
                                    />
                                </div>
                            </div>

                            <div className="col-sm-12">
                                <div className="form-group">
                                    <label className="control-label">Price</label>
                                    <input
                                        className="form-control"
                                        name='price'
                                        type="number"
                                        id="price"
                                        value={subService.price || ''}
                                        onChange={handleChange}
                                        placeholder='Price of the sub-service'
                                    />
                                </div>
                            </div>
                        </div>
                    </Modal.Body>

                    <Modal.Footer>
                        <Button
                            type="button"
                            className="btn btn-secondary"
                            onClick={resetForm}
                        >
                            Close
                        </Button>
                        <Button
                            type="button"
                            onClick={handleSubServices}
                            className="btn btn-primary"
                        >
                            Submit
                        </Button>
                    </Modal.Footer>
                </Modal>
            )}
        </div>
    );
};

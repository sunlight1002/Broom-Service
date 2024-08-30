import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useAlert } from 'react-alert';
import Swal from 'sweetalert2';
import { Modal, Button, Table,Form } from 'react-bootstrap';
import { useTranslation } from "react-i18next";

export default function WorkerAdvance({ worker }) {
    const [advances, setAdvances] = useState([]);
    const { t, i18n } = useTranslation();
    const [formData, setFormData] = useState({
        worker_id: worker.id,
        type: '',
        amount: '',
        monthly_payment: '',
        loan_start_date: '',
    });
    const [isEditing, setIsEditing] = useState(false);
    const [currentAdvanceId, setCurrentAdvanceId] = useState(null);
    const [showModal, setShowModal] = useState(false);

    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

   

    const fetchAdvances = async () => {
        try {
            const response = await axios.get(`/api/admin/advance-loans/${worker.id}`, { headers });
            setAdvances(response.data);
        } catch (error) {
            alert.error('Failed to fetch advances/loans.');
        }
    };
    useEffect(() => {
        fetchAdvances();
    }, []);

    const handleInputChange = (e) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        let res
        try {
            if (isEditing) {
               let res = await axios.post(`/api/admin/advance-loans/${currentAdvanceId}`, formData, { headers });
            } else {
               let res = await axios.post('/api/admin/advance-loans', formData, { headers });
            }
                alert.success(isEditing ? 'Advance/Loan updated successfully.' : 'Advance/Loan created successfully.');
                fetchAdvances();
                setShowModal(false);
                setIsEditing(false);
                setFormData({ worker_id: worker.id, type: '', amount: '', monthly_payment: '', loan_start_date: '' });
            } catch (error) {
                if (error.response && error.response.data.errors) {
                    const errors = error.response.data.errors;
                    Object.keys(errors).forEach((field) => {
                        alert.error(errors[field][0]); 
                    },2000);
                } else {
                    alert.error("An unexpected error occurred.");
                }
                setShowError(true);
                setTimeout(() => setShowError(false), 5000);
            }
    };

    const handleEdit = (advance) => {
        setFormData({
            worker_id: worker.id,
            type: advance.type,
            amount: advance.amount,
            monthly_payment: advance.monthly_payment,
            loan_start_date: advance.loan_start_date,
        });
        setIsEditing(true);
        setCurrentAdvanceId(advance.id);
        setShowModal(true); 
    };

    const handleDelete = (id) => {
        Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {
            if (result.isConfirmed) {
                axios
                    .delete(`/api/admin/advance-loans/${id}`, { headers })
                    .then(() => {
                        Swal.fire("Deleted!", "Advance/Loan has been deleted.", "success");
                        fetchAdvances();
                    })
                    .catch((error) => {
                        if (error.response && error.response.status === 400) {
                            Swal.fire("Error", error.response.data.message || 'Failed to delete advance/loan.', "error");
                        } else {
                            Swal.fire("Error", 'Failed to delete advance/loan.', "error");
                        }
                        setShowError(true);
                        setTimeout(() => setShowError(false), 5000);
                    });
            }
        });
    };

    const handleOpenModal = () => {
        setIsEditing(false);
        setFormData({
            worker_id: worker.id,
            type: '',
            amount: '',
            monthly_payment: '',
            loan_start_date: '',
        });
        setShowModal(true);
    };

    const handleCloseModal = () => {
        setShowModal(false);
        setCurrentAdvanceId(null);
    };
    
    const formatCurrency = (amount) => {
        if (amount === null || amount === undefined) {
            return 'N/A';
        }
        return `₪${parseFloat(amount).toFixed(2)}`;
    };

    return (
        
        <div>
        <Button variant="primary" onClick={handleOpenModal}>
           {t("worker.settings.addAdvance")}
        </Button>

        <Table striped bordered hover>
            <thead>
                <tr>
                    <th>{t("worker.settings.type")}</th>
                    <th>Date</th>
                    <th>Monthly Payment</th>
                    <th>{t("worker.settings.amount")}</th>
                    <th>Paid Amount</th>
                    <th>{t("worker.settings.pendingAmount")}</th>
                    <th>Status</th>
                    <th>{t("worker.settings.action")}</th>
                </tr>
            </thead>
            <tbody>
                {advances.map((advance) => (
                    <tr key={advance.id}>
                        <td>{advance.type}</td>
                        <td>{advance.created_at}</td>
                        <td>{formatCurrency(advance.monthly_payment)}</td>
                        <td>{formatCurrency(advance.amount)}</td>
                        <td>{formatCurrency(advance.total_paid_amount)}</td>
                        <td>{formatCurrency(advance.latest_pending_amount)}</td>
                        <td style={{ 
                            color: 
                                advance.status === 'paid' ? 'green' : 
                                advance.status === 'active' ? 'orange' : 
                                advance.status === 'pending' ? 'red' : 
                                'black',
                                fontWeight: 'bold'
                        }}>
                            {advance.status}
                        </td>
                        <td>
                        <div className="action-dropdown dropdown">
                                    <button className="btn btn-default dropdown-toggle" type="button" id={`dropdownMenuButton-${advance.id}`} data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i className="fa fa-ellipsis-vertical"></i>
                                    </button>
                                    <div className="dropdown-menu" aria-labelledby={`dropdownMenuButton-${advance.id}`}>
                                        <button
                                            type="button"
                                            className="dropdown-item"
                                            onClick={() => handleEdit(advance)}
                                        >
                                            {t("admin.leads.Edit")}
                                        </button>
                                        <button
                                            type="button"
                                            className="dropdown-item"
                                            onClick={() => handleDelete(advance.id)}
                                        >
                                             {t("admin.leads.Delete")}
                                        </button>
                                    </div>
                                </div>
                            </td>
                    </tr>
                ))}
            </tbody>
        </Table>

         {/* Modal for Add/Edit */}
         <Modal show={showModal} onHide={handleCloseModal} size="lg" >
                <Modal.Header closeButton>
                    <Modal.Title>{isEditing ? t("worker.settings.editAdvance") : t("worker.settings.addAdvance")}</Modal.Title>
                </Modal.Header>
                <Modal.Body>
                    <Form onSubmit={handleSubmit}>
                        <Form.Group controlId="formType" className="mb-3">
                            <Form.Label>{t("worker.settings.type")}:</Form.Label>
                            <Form.Control as="select" name="type" value={formData.type} onChange={handleInputChange} >
                                <option value="">{t("worker.settings.selectType")}</option>
                                <option value="advance">{t("worker.settings.Advance")}</option>
                                <option value="loan">{t("worker.settings.loan")}</option>
                            </Form.Control>
                        </Form.Group>
                        <Form.Group controlId="formAmount" className="mb-3">
                            <Form.Label>{t("worker.settings.amount")}:</Form.Label>
                            <Form.Control type="number" name="amount" value={formData.amount} onChange={handleInputChange}  />
                        </Form.Group>
                        {formData.type === 'loan' && (
                            <>
                                <Form.Group controlId="formMonthlyPayment" className="mb-3">
                                    <Form.Label>{t("worker.settings.monthlyPayment")}:</Form.Label>
                                    <Form.Control type="number" name="monthly_payment" value={formData.monthly_payment} onChange={handleInputChange} />
                                </Form.Group>
                                <Form.Group controlId="formLoanStartDate" className="mb-3">
                                    <Form.Label>{t("worker.settings.loanStart")}:</Form.Label>
                                    <Form.Control type="date" name="loan_start_date" value={formData.loan_start_date} onChange={handleInputChange} />
                                </Form.Group>
                            </>
                        )}
                        <Button type="submit" variant="primary">
                            {isEditing ? t("worker.settings.update") : t("worker.settings.submit")}
                        </Button>
                    </Form>
                </Modal.Body>
            </Modal>
    </div>
    );
}

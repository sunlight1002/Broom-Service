import React, { useState, useEffect } from "react";
import { useAlert } from "react-alert";
import { useTranslation } from "react-i18next";
import FullPageLoader from "../../../Components/common/FullPageLoader";


function BankDetails() {
    const { t } = useTranslation();
    const [errors, setErrors] = useState([]);
    const [loading, setLoading] = useState(false);
    const alert = useAlert();
    const [payment, setPayment] = useState("")
    const [bankDetails, setBankDetails] = useState({
        payment_type: "",
        full_name: "",
        bank_name: "",
        bank_no: null,
        branch_no: null,
        account_no: null
    })

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setBankDetails((prevDetails) => ({
            ...prevDetails,
            [name]: value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        const formData = new FormData();
        formData.append("payment_type", payment);
        formData.append("bank_number", bankDetails.bank_no);
        formData.append("bank_name", bankDetails.bank_name);
        formData.append("branch_number", bankDetails.branch_no);
        formData.append("full_name", bankDetails.full_name);
        formData.append("account_number", bankDetails.account_no);

        axios
            .post(`/api/admin/change-bank-details`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {                    
                    setLoading(false)
                    setErrors(response.data.errors);
                    for (let e in response.data.errors) {
                        alert.error(response.data.errors[e]);
                    }
                } else {
                    setLoading(false)
                    alert.success("Bank Details has been updated successfully");
                }
            });
    };

    const getSetting = async () => {

        try {
            setLoading(true);
            const response = await axios.get("/api/admin/my-account", { headers });
            const data = response.data.account;
            setPayment(data.payment_type)
            setBankDetails({
                payment_type: data.payment_type,
                full_name: data.full_name,
                branch_no: data.branch_number,
                account_no: data.account_number,
                bank_name: data.bank_name,
                bank_no: data.bank_number
            })
            setLoading(false)
        } catch (error) {
            setLoading(false)
            console.error("Error fetching settings:", error);
        }
    };

    useEffect(() => {
        getSetting();
    }, []);

    return (
        <div className="card" style={{ boxShadow: "none" }}>
            <div className="card-body">
                <form>
                    <div className="form-group">
                        <label className="control-label">
                            Payment Method
                        </label>

                        <select
                            className="form-control"
                            value={payment}
                            onChange={(e) =>
                                setPayment(e.target.value)
                            }
                        >
                            <option value="">--- please select ---</option>
                            <option value="cheque">Cheque</option>
                            <option value="money_transfer">Money Transfer</option>
                        </select>

                    </div>
                    {
                        payment === "money_transfer" && (
                            <div className="col-sm-12 mt-2">
                                <div className="dashBox p-4">
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="form-group">
                                                <label className="control-label">Full Name</label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.full_name}
                                                    name="full_name"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder="Enter Full Name"
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="form-group">
                                                <label className="control-label">Bank Name</label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.bank_name}
                                                    name="bank_name"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder="Enter Bank Name"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="form-group">
                                                <label className="control-label">Bank Number</label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.bank_no}
                                                    name="bank_no"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder="Enter Bank Number"
                                                />
                                            </div>
                                        </div>
                                        <div className="col-md-6">
                                            <div className="form-group">
                                                <label className="control-label">Branch Number</label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.branch_no}
                                                    name="branch_no"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder="Enter Branch Number"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div className="row">
                                        <div className="col-md-6">
                                            <div className="form-group">
                                                <label className="control-label">Account Number</label>
                                                <input
                                                    type="text"
                                                    value={bankDetails.account_no}
                                                    name="account_no"
                                                    onChange={handleChange}
                                                    className="form-control"
                                                    placeholder="Enter Account Number"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        )
                    }
                    <div className="form-group mt-3 text-center">
                        <input
                            type="submit"
                            onClick={handleSubmit}
                            value={t("client.jobs.review.Submit")}
                            className="btn navyblue saveBtn"
                        />
                    </div>
                </form>
            </div>

            {loading && <FullPageLoader visible={loading} />}
        </div>
    )
}

export default BankDetails
import React, { useEffect, useState } from 'react'
import { useAlert } from 'react-alert';

export default function Icount({ settings , refreshSettings }) {


    const [companyID, setCompanyID] = useState("");
    const [username, setUsername]   = useState("");
    const [password, setPassword]   = useState("");
    const [errors, setErrors] = useState([]);
    const alert = useAlert();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append("for",'icount');
        formData.append("icount_company_id", companyID);
        formData.append("icount_username", username);
        formData.append("icount_password", password);
        axios
            .post(`/api/admin/update-settings`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    refreshSettings();
                    setErrors([])
                    alert.success(
                        "Icount details has been updated successfully"
                    );
                }
            });
    };


    useEffect(()=>{

       
        settings.icount_company_id && setCompanyID( settings.icount_company_id )
        settings.icount_username && setUsername( settings.icount_username )
        settings.icount_password && setPassword( settings.icount_password );


    },[ settings ])


    return (
        <div className='card'>
            <div className='card-body'>
                <form>
                    <div className='form-group'>
                        <label className='control-label'>Company ID *</label>
                        <input type='text' value={companyID} onChange={(e) => setCompanyID(e.target.value)} className='form-control' placeholder='Enter company Id' />
                        {errors.icount_company_id ? (
                            <small className="text-danger mb-1">
                                {errors.icount_company_id}
                            </small>
                        ) : (
                            ""
                        )}
                    </div>
                    <div className='form-group'>
                        <label className='control-label'>Username *</label>
                        <input type='text' value={username} onChange={(e) => setUsername(e.target.value)} className='form-control' placeholder='Enter username' />
                        {errors.icount_username ? (
                            <small className="text-danger mb-1">
                                {errors.icount_username}
                            </small>
                        ) : (
                            ""
                        )}
                    </div>
                    <div className='form-group'>
                        <label className='control-label'>Password *</label>
                        <input type='text' value={password} onChange={(e) => setPassword(e.target.value)} className='form-control' placeholder='Enter password' />
                        {errors.icount_password ? (
                            <small className="text-danger mb-1">
                                {errors.icount_password}
                            </small>
                        ) : (
                            ""
                        )}
                    </div>
                    <div className="form-group text-center">
                        <input type='submit' value='SAVE' onClick={handleSubmit} className="btn btn-danger saveBtn" />
                    </div>
                </form>
            </div>
        </div>

    )
}

import React, { useEffect, useState } from 'react'
import { useAlert } from 'react-alert';

export default function Zcredit({ settings, refreshSettings }) {

    const [key, setKey]                          = useState("");
    const [terminalNumnber, setTerminalNumber]   = useState("");
    const [terminalPass, setTerminalPass]        = useState("");
    
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
        formData.append("for",'zcredit');
        formData.append("zcredit_key", key);
        formData.append("zcredit_terminal_number", terminalNumnber);
        formData.append("zcredit_terminal_pass", terminalPass);
        axios
            .post(`/api/admin/update-settings`, formData, { headers })
            .then((response) => {
                if (response.data.errors) {
                    setErrors(response.data.errors);
                } else {
                    refreshSettings();
                    setErrors([])
                    alert.success(
                        "Zcredit details has been updated successfully"
                    );
                }
            });
    };

    useEffect(()=>{

      
        settings.zcredit_key ?? setKey( settings.zcredit_key )
        settings.zcredit_terminal_number ?? setTerminalNumber( settings.zcredit_terminal_number )
        settings.zcredit_terminal_pass ?? setTerminalPass( settings.zcredit_terminal_pass );


    },[ settings ])


    return (
        <div className='card'>
            <div className='card-body'>
                <form>
                    <div className='form-group'>
                        <label className='control-label'>Key *</label>
                        <input type='text' value={key} onChange={(e) => setKey(e.target.value)} className='form-control' placeholder='Enter key' />
                        {errors.zcredit_key ? (
                            <small className="text-danger mb-1">
                                {errors.zcredit_key}
                            </small>
                        ) : (
                            ""
                        )}
                    </div>
                    <div className='form-group'>
                        <label className='control-label'>Terminal Number *</label>
                        <input type='text' value={terminalNumnber} onChange={(e) => setTerminalNumber(e.target.value)} className='form-control' placeholder='Enter terminal number' />
                        {errors.zcredit_terminal_number ? (
                            <small className="text-danger mb-1">
                                {errors.zcredit_terminal_number}
                            </small>
                        ) : (
                            ""
                        )}
                    </div>
                    <div className='form-group'>
                        <label className='control-label'>Terminal Password *</label>
                        <input type='text' value={terminalPass} onChange={(e) => setTerminalPass(e.target.value)} className='form-control' placeholder='Enter terminal password' />
                        {errors.zcredit_terminal_pass ? (
                            <small className="text-danger mb-1">
                                {errors.zcredit_terminal_pass}
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

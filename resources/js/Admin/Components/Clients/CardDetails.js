import React, { useState, useEffect } from 'react'

export default function CardDetails({ latestContract, client }) {

    const [pass, setPass] = useState(null);
    const [passVal, setPassVal] = useState(null);
    const [token,setToken] = useState(null);

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    const getToken = () =>{

        if( client.latest_contract != 0 && client.latest_contract != undefined && client != undefined){
        axios.get(`/api/admin/card_token/${client.id}`,{ headers })
        .then((res) => {
            setToken( res.data.token != 0 ? res.data.token : null );
        })
    }

    }
    
    const viewPass = () => {

        if (!passVal) { window.alert('Please enter your password'); return; }
        axios
            .post(`/api/admin/viewpass`, { id: localStorage.getItem('admin-id'), pass: passVal }, { headers })
            .then((res) => {
                if (res.data.response == false) {
                    window.alert('Wrong password!');
                } else {
                     setCvv(or_cvv);
                    document.querySelector('.closeCv').click();
                }
            })
    }
    
    useEffect(() => {
        getToken();
        setTimeout(() => {
            if (client.latest_contract != 0 && client.latest_contract != undefined) {
                let bookBtn = document.querySelector('#bookBtn');
                bookBtn.style.display = 'block';
            }
        }, 200)
    }, [client]);
    return (
        <div className='form-group'>
            <ul className='list-unstyled'>
                <li><strong>Card Token : </strong>{ token != null ? token : 'NA' }</li>
              
            </ul>
            <div className="modal fade" id="exampleModalPassCv" tabindex="-1" role="dialog" aria-labelledby="exampleModalPassCv" aria-hidden="true">
                <div className="modal-dialog" role="document">
                    <div className="modal-content">
                        <div className="modal-header">
                            <button type="button" className="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div className="modal-body">

                            <div className="row">
                                <div className="col-sm-12">
                                    <div className="form-group">
                                        <label className="control-label">
                                            Enter your password
                                        </label>
                                        <input
                                            type="password"
                                            onChange={(e) =>
                                                setPassVal(e.target.value)
                                            }
                                            className="form-control"
                                            required
                                            placeholder="Enter your password"
                                        />

                                    </div>
                                </div>

                            </div>


                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary closeCv" data-dismiss="modal">Close</button>
                            <button type="button" onClick={viewPass} className="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}

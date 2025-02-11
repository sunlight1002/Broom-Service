import { useState, useEffect } from "react";
import Select from "react-select";
import { useTranslation } from "react-i18next";
import axios from "axios";
import PhoneInput from "react-phone-input-2";
import { useAlert } from "react-alert";

const ContactsTable = ({ clientId, client }) => {
    const [allAddress, setAllAddress] = useState([]);
    const [errors, setErrors] = useState({});
    const [paymentChecked, setPaymentChecked] = useState(false);
    const [contactExistence, setContactExistence] = useState({
        client: false,
        matched_addresses: [],
        addresses: [],
    });
    const [contactPersons, setContactPersons] = useState([
        { name: "", phone: "", addresses: [], addressCheck: false, paymentCheck: false },
    ]);
    const alert = useAlert();

    const { t } = useTranslation();

    const headers = {
        Accept: "application/json, text/plain, */*",
        "Content-Type": "application/json",
        Authorization: `Bearer ` + localStorage.getItem("admin-token"),
    };

    useEffect(() => {
        axios
            .get(`/api/admin/clients/${parseInt(clientId)}/edit`, { headers })
            .then((res) => {
                const { client } = res.data;
                if (client.property_addresses && client.property_addresses.length > 0) {
                    const mapAddress = client.property_addresses.map((w) => ({
                        value: w.id,
                        label: `${w.address_name}`,
                    }));
                    setAllAddress(mapAddress);
                }
            });
    }, [clientId]);


    useEffect(() => {
        if (contactExistence.client || contactExistence.addresses?.length > 0) {
            let newContacts = [];


            if (contactExistence.client && contactExistence.matched_addresses?.length > 0) {
                contactExistence.matched_addresses.forEach((addrId) => {
                    newContacts.push({
                        name: client.contact_person_name || "",
                        phone: client.contact_person_phone || "",
                        addresses: allAddress.filter(addr => addr.value === addrId),
                        addressCheck: true,
                        paymentCheck: true,
                    });
                    setPaymentChecked(true);
                });
                } 
                
                if (contactExistence.addresses?.length > 0) {
                    contactExistence?.addresses?.map((add, index) => {
                        newContacts.push({
                            name: add.contact_person_name || "",
                            phone: add.contact_person_phone || "",
                            addresses: allAddress.filter(addr => addr.value === add.id),
                            addressCheck: true,
                            paymentCheck: false,
                        });
                    });
                }

                setContactPersons(newContacts);
            }
        }, [contactExistence, allAddress, client]);



    const handleSubmit = async (e) => {
        e.preventDefault();

        // Validation
        const newErrors = {};
        contactPersons.forEach((contact, index) => {
            if (!contact.name.trim()) newErrors[`contact_person_name_${index}`] = t("admin.leads.AddLead.contact_person_name_required");
            if (!contact.phone.trim()) newErrors[`contact_person_phone_${index}`] = t("admin.leads.AddLead.contact_person_phone_required");
        });

        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        // Prepare Data for Submission
        const formattedData = contactPersons.map(contact => ({
            name: contact.name,
            phone: contact.phone,
            address_notification: contact.addressCheck,
            payment_notification: contact.paymentCheck,
            addresses: contact.addresses.map(addr => addr.value), // Extracting address IDs
        }));

        console.log(formattedData);
        

        // try {
        //     const response = await axios.post(`/api/admin/add-contacts/${clientId}`, formattedData, { headers });

        //     if (response.status === 200) {
        //         alert.success(response.data.message);
        //         setContactPersons([{ name: "", phone: "", addresses: [], addressCheck: false, paymentCheck: false }]);
        //         setErrors({});
        //     }
        // } catch (error) {
        //     console.error("Error submitting contact persons:", error);
        // }
    };


    const fetchContactExistence = async () => {
        try {
            const response = await axios.get(`/api/admin/get-contacts/${clientId}`, {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "application/json",
                    Authorization: `Bearer ` + localStorage.getItem("admin-token"),
                },
            });
            console.log(response.data);


            if (response.status === 200) {
                const { exist, matched_addresses, unique_addresses } = response.data;

                // Update state based on response
                setContactExistence({
                    client: exist === "client",
                    addresses: unique_addresses || [],
                    matched_addresses: matched_addresses || [],
                });
            }
        } catch (error) {
            console.error("Error fetching contact existence data:", error);
        }
    };

    useEffect(() => {
        fetchContactExistence();
    }, [clientId]);


    const handleCheckbox = (index, field) => {
        const updatedContacts = [...contactPersons];
        updatedContacts[index][field] = !updatedContacts[index][field];

        if (field === "paymentCheck" && updatedContacts[index][field]) {
            setPaymentChecked(true);  // Set flag when a payment checkbox is checked
        } else if (field === "paymentCheck" && !updatedContacts.some(c => c.paymentCheck)) {
            setPaymentChecked(false); // Reset flag if no checkbox is checked
        }

        setContactPersons(updatedContacts);
    };


    const addContactPerson = () => {
        setContactPersons([
            ...contactPersons,
            { name: "", phone: "", addresses: [], addressCheck: false, paymentCheck: !paymentChecked }
        ]);
    };



    const removeContactPerson = (index) => {
        const updatedContacts = contactPersons.filter((_, i) => i !== index);

        // Check if the removed row had paymentCheck enabled
        const removedRowHadPaymentChecked = contactPersons[index].paymentCheck;

        if (removedRowHadPaymentChecked) {
            // Check if any remaining rows still have paymentCheck checked
            const anyPaymentCheckLeft = updatedContacts.some(contact => contact.paymentCheck);

            setPaymentChecked(anyPaymentCheckLeft);
        }

        setContactPersons(updatedContacts);
    };


    const updateContactPerson = (index, key, value) => {
        const updatedContacts = [...contactPersons];
        updatedContacts[index][key] = value;
        setContactPersons(updatedContacts);
    };

    return (
        <div className="mt-3">
            <div className="row">
                <div className="col-sm-6 d-flex justify-content-end">
                    <button type="button" className="btn btn-primary" onClick={addContactPerson}>
                        <i className="fa fa-plus"></i>{" "}
                    </button>
                </div>
            </div>
            {contactPersons.map((contactPerson, index) => (
                <div key={index} className="mb-3">
                    <div className="row">
                        <div className="col-sm-3 d-flex align-items-center">
                            <div className="form-group mb-0 navyblueColor d-flex align-items-center">
                                <label htmlFor={`address_notification_${index}`} className="control-label navyblueColor">
                                    {t("global.address_contact")}
                                </label>
                                <input
                                    type="checkbox"
                                    name="address_notification"
                                    id={`address_notification_${index}`}
                                    className="mx-2"
                                    checked={contactPerson.addressCheck}
                                    onChange={() => handleCheckbox(index, "addressCheck")}
                                />
                            </div>
                            {
                                (!paymentChecked || contactPerson.paymentCheck) && (
                                    <div className="form-group mb-0 navyblueColor d-flex align-items-center mx-2">
                                        <label htmlFor={`payment_notification_${index}`} className="control-label navyblueColor">
                                            {t("global.payment_contact")}
                                        </label>
                                        <input
                                            type="checkbox"
                                            name="payment_notification"
                                            id={`payment_notification_${index}`}
                                            className="mx-2"
                                            checked={contactPerson.paymentCheck}
                                            onChange={() => handleCheckbox(index, "paymentCheck")}
                                        />
                                    </div>
                                )
                            }

                        </div>
                        <div className="col-sm-2 d-flex align-items-center">
                            <button type="button" className="btn btn-danger btn-sm" onClick={() => removeContactPerson(index)}>
                                <i className="fa fa-minus"></i>{" "}
                            </button>
                        </div>
                    </div>

                    <div className="row mt-2">
                        <div className="col-sm-3">
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label navyblueColor mr-2">{t("workerInviteForm.phone")}</label>
                                <div className="d-flex flex-column">
                                    <PhoneInput
                                        country={"il"}
                                        value={contactPerson.phone}
                                        onChange={(phone, country) => {
                                            const dialCode = country.dialCode;
                                            let formattedPhone = phone;
                                            if (phone.startsWith(dialCode + "0")) {
                                                formattedPhone = dialCode + phone.slice(dialCode.length + 1);
                                            }
                                            updateContactPerson(index, "phone", formattedPhone);
                                        }}
                                        inputClass="form-control"
                                        inputProps={{ name: "phone" }}
                                    />
                                    {errors.contact_person_phone && <small className="text-danger mb-1">{errors.contact_person_phone}</small>}
                                </div>
                            </div>
                        </div>

                        <div className="col-sm-3">
                            <div className="form-group d-flex align-items-center">
                                <label className="control-label mb-0 navyblueColor mr-2">{t("global.name")}</label>
                                <div className="d-flex flex-column">
                                    <input
                                        name="contact_person_name"
                                        type="text"
                                        className="form-control skyBorder"
                                        value={contactPerson.name}
                                        onChange={(e) => updateContactPerson(index, "name", e.target.value)}
                                        placeholder={t("admin.leads.AddLead.contact_person_name_placeholder")}
                                    />
                                    {errors.contact_person_name && <small className="text-danger mb-1">{errors.contact_person_name}</small>}
                                </div>
                            </div>
                        </div>

                        {contactPerson.addressCheck && (
                            <div className="col-sm-6 d-flex align-items-center">
                                <div className="mr-3" style={{ fontWeight: "bold" }}>{t("price_offer.address_text")}</div>
                                <div className="d-flex align-items-center flex-wrap">
                                    <Select
                                        value={contactPerson.addresses || contactExistence.address}
                                        name="clients"
                                        isMulti
                                        options={allAddress}
                                        className="basic-multi-single skyBorder"
                                        isClearable={true}
                                        placeholder={t("admin.leads.AddLead.addAddress.Options.pleaseSelect")}
                                        classNamePrefix="select"
                                        onChange={(newValue) => updateContactPerson(index, "addresses", newValue)}
                                    />
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            ))}

            {
                contactPersons.length > 0 && (
                    <div className="row">
                        <div className="col-sm-6 d-flex justify-content-end">
                            <button type="button" className="btn btn-primary" onClick={handleSubmit}>
                                {t("global.save")}
                            </button>
                        </div>
                    </div>
                )
            }
        </div>
    );
};

export default ContactsTable;

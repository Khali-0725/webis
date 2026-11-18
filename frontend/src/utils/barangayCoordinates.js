const TANZA_COORDINATES = {
  'Amaya I': [14.3886141, 120.8403530], 'Bagtas': [14.3349294, 120.8533218],
  'Biga': [14.3619185, 120.8558721], 'Biwas': [14.3990841, 120.8554803],
  'Bucal': [14.3912205, 120.8628635], 'Bukal': [14.3912205, 120.8628635],
  'Bunga': [14.3552045, 120.8716063], 'Calibuyo': [14.3599172, 120.8094016],
  'Capipisa': [14.3589392, 120.7922029], 'Daang Amaya I': [14.3919658, 120.8552146],
  'Halayhay': [14.3740223, 120.8122626], 'Julugan I': [14.4053429, 120.8428788],
  'Julugan II': [14.4050494, 120.8471124], 'Julugan III': [14.4072455, 120.8444373],
  'Julugan IV': [14.4021313, 120.8442870], 'Julugan V': [14.4029325, 120.8426895],
  'Julugan VI': [14.3992794, 120.8437402], 'Julugan VII': [14.4011116, 120.8422161],
  'Julugan VIII': [14.3991157, 120.8412201], 'Mulawin': [14.3815757, 120.8545483],
  'Paradahan I': [14.3207518, 120.8607990], 'Paradahan II': [14.3253489, 120.8600210],
  'Punta I': [14.3520856, 120.8598544], 'Punta II': [14.3417364, 120.8598857],
  'Sahud Ulan': [14.3710092, 120.8240641], 'Sahud-Ulan': [14.3710092, 120.8240641],
  'Sanja Mayor': [14.3750722, 120.8541510], 'Santol': [14.3750474, 120.8734517],
  'Tanauan': [14.2923273, 120.8290803], 'Tres Cruses': [14.3311487, 120.8327066],
  'Lambingan': [14.3527110, 120.7999306], 'Amaya II': [14.3815031, 120.8335619],
  'Amaya III': [14.3829976, 120.8340822], 'Amaya IV': [14.3842637, 120.8308653],
  'Amaya V': [14.3900179, 120.8291408], 'Amaya VI': [14.3774988, 120.8326493],
  'Amaya VII': [14.3885388, 120.8290180], 'Daang Amaya II': [14.3961947, 120.8515357],
  'Daang Amaya III': [14.3955895, 120.8549104],
  'Barangay I (Pob.)': [14.4007024, 120.8577708], 'Poblacion I': [14.4007024, 120.8577708],
  'Barangay II (Pob.)': [14.3990881, 120.8575053], 'Poblacion II': [14.3990881, 120.8575053],
  'Barangay III (Pob.)': [14.3981940, 120.8561711], 'Poblacion III': [14.3981940, 120.8561711],
  'Barangay IV (Pob.)': [14.3972059, 120.8596404], 'Poblacion IV': [14.3972059, 120.8596404],
};

export function withCorrectedBarangayCoordinates(barangay) {
  if (!barangay || barangay.municipality !== 'Tanza') return barangay;
  const coordinates = TANZA_COORDINATES[barangay.name];
  return coordinates ? { ...barangay, latitude: coordinates[0], longitude: coordinates[1] } : barangay;
}
